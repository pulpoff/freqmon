<?php
/**
 * API endpoint for fetching dashboard data via AJAX
 * Returns JSON response for real-time updates without page reload
 */

// Enable gzip compression if supported
if (!ob_start('ob_gzhandler')) {
    ob_start();
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/src/Config.php';
require_once __DIR__ . '/src/Cache.php';
require_once __DIR__ . '/src/FreqtradeClient.php';
require_once __DIR__ . '/src/ParallelHttp.php';
require_once __DIR__ . '/src/EquityTracker.php';
require_once __DIR__ . '/src/LoginGuard.php';
require_once __DIR__ . '/src/AuthSession.php';
require_once __DIR__ . '/src/Dashboard.php';

use FreqtradeDashboard\Config;
use FreqtradeDashboard\Cache;
use FreqtradeDashboard\Dashboard;
use FreqtradeDashboard\FreqtradeClient;
use FreqtradeDashboard\AuthSession;

try {
    $config = Config::getInstance();
    date_default_timezone_set($config->getTimezone());

    $configPassword = $config->getPassword();
    $authRequired = $configPassword !== null;
    $session = new AuthSession();

    // Gate for every data-returning endpoint: without a valid session (when a
    // password is configured) nothing is served. This is the real protection —
    // the password modal alone is only cosmetic.
    $requireAuth = function () use ($authRequired, $session) {
        if ($authRequired && !$session->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'auth_required' => true, 'error' => 'Authentication required']);
            exit;
        }
    };

    $action = isset($_GET['action']) ? $_GET['action'] : '';

    if ($action === 'check_auth') {
        $banned = false;
        $retryAfter = 0;
        $guardCfg = $config->getGuard();
        if ($guardCfg !== null && $authRequired) {
            $guard = new \FreqtradeDashboard\LoginGuard($guardCfg['tries'], $guardCfg['hours'] * 3600);
            $st = $guard->check($guard->getClientIp());
            $banned = $st['banned'];
            $retryAfter = $st['retry_after'];
        }
        echo json_encode([
            'success' => true,
            'auth_required' => $authRequired,
            'authenticated' => !$authRequired || $session->isAuthenticated(),
            'banned' => $banned,
            'retry_after' => $retryAfter,
        ]);
        exit;
    }

    if ($action === 'verify_password') {
        $guardCfg = $config->getGuard();
        $guard = null;
        $ip = null;

        if ($guardCfg !== null) {
            $throttle = isset($guardCfg['throttle']) ? (float) $guardCfg['throttle'] : 2.0;
            $guard = new \FreqtradeDashboard\LoginGuard($guardCfg['tries'], $guardCfg['hours'] * 3600, $throttle);
            $ip = $guard->getClientIp();

            // 1) Hard ban check
            $status = $guard->check($ip);
            if ($status['banned']) {
                http_response_code(429);
                header('Retry-After: ' . $status['retry_after']);
                echo json_encode([
                    'success' => false,
                    'banned' => true,
                    'retry_after' => $status['retry_after'],
                    'error' => 'Too many failed attempts. Locked for ' . ceil($status['retry_after'] / 60) . ' more minute(s).',
                ]);
                exit;
            }

            // 2) Rate limit: reject attempts that come in too fast
            $thr = $guard->throttle($ip);
            if ($thr['throttled']) {
                http_response_code(429);
                header('Retry-After: ' . $thr['retry_after']);
                echo json_encode([
                    'success' => false,
                    'throttled' => true,
                    'retry_after' => $thr['retry_after'],
                    'error' => 'Slow down — please wait a moment before trying again.',
                ]);
                exit;
            }
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $providedPassword = (string) (is_array($input) ? ($input['password'] ?? '') : '');

        // Constant-time comparison to avoid leaking the password via timing.
        if ($configPassword === null || hash_equals((string) $configPassword, $providedPassword)) {
            if ($guard !== null) {
                $guard->recordSuccess($ip);
            }
            $session->issue(); // mint server-side session + HttpOnly cookie
            echo json_encode(['success' => true]);
            exit;
        }

        if ($guard !== null) {
            $res = $guard->recordFailure($ip);
            if ($res['banned']) {
                http_response_code(429);
                header('Retry-After: ' . $res['retry_after']);
                echo json_encode([
                    'success' => false,
                    'banned' => true,
                    'retry_after' => $res['retry_after'],
                    'error' => 'Too many failed attempts. Locked for ' . ceil($res['retry_after'] / 60) . ' minute(s).',
                ]);
                exit;
            }
            echo json_encode([
                'success' => false,
                'remaining' => $res['remaining'],
                'error' => 'Invalid password. ' . $res['remaining'] . ' attempt(s) left before lockout.',
            ]);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Invalid password']);
        exit;
    }

    // Handle pair_candles request
    if ($action === 'pair_candles') {
        $requireAuth();
        ob_clean(); // Clear any previous output

        $serverNum = isset($_GET['server']) ? intval($_GET['server']) : 1;
        $pair = isset($_GET['pair']) ? $_GET['pair'] : '';
        $timeframe = isset($_GET['timeframe']) ? $_GET['timeframe'] : '5m';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;

        // Security: Validate pair format (e.g., BTC/USDT, ETH/USDT:USDT)
        if (empty($pair) || !preg_match('/^[A-Z0-9]{2,10}\/[A-Z0-9]{2,10}(:[A-Z0-9]{2,10})?$/i', $pair)) {
            echo json_encode(['success' => false, 'error' => 'Invalid pair format']);
            exit;
        }

        // Security: Validate timeframe against allowed values
        $allowedTimeframes = ['1m', '3m', '5m', '15m', '30m', '1h', '2h', '4h', '6h', '8h', '12h', '1d', '3d', '1w', '1M'];
        if (!in_array($timeframe, $allowedTimeframes)) {
            echo json_encode(['success' => false, 'error' => 'Invalid timeframe']);
            exit;
        }

        // Security: Limit the limit parameter
        $limit = max(1, min($limit, 500));

        $servers = $config->getServers();
        if (!isset($servers[$serverNum])) {
            echo json_encode(['success' => false, 'error' => 'Server not found']);
            exit;
        }

        $serverConfig = $servers[$serverNum];

        // Cache pair candles for 60 seconds (longer TTL since chart data changes less frequently)
        $cache = Cache::getInstance();
        $cacheKey = "candles_{$serverNum}_{$pair}_{$timeframe}_{$limit}";

        $result = $cache->remember($cacheKey, function () use ($serverConfig, $pair, $timeframe, $limit) {
            $client = new FreqtradeClient(
                $serverConfig['host'],
                $serverConfig['username'],
                $serverConfig['password']
            );

            $candles = $client->getPairCandles($pair, $timeframe, $limit);

            if ($candles === null) {
                $error = $client->getLastError();
                return [
                    'success' => false,
                    'error' => 'Failed to fetch candles: ' . ($error['message'] ?? 'Unknown error'),
                    'debug' => $error
                ];
            }

            return [
                'success' => true,
                'data' => $candles
            ];
        }, 60); // 60 seconds TTL for candle data

        echo json_encode($result);
        exit;
    }
    
    // Handle logs request
    if ($action === 'logs') {
        $requireAuth();
        ob_clean(); // Clear any previous output

        if (!$config->isLogsEnabled()) {
            echo json_encode(['success' => false, 'error' => 'Logs are disabled']);
            exit;
        }

        $serverNum = isset($_GET['server']) ? intval($_GET['server']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 500;
        $limit = max(1, min($limit, 1000));

        $servers = $config->getServers();
        if (!isset($servers[$serverNum])) {
            echo json_encode(['success' => false, 'error' => 'Server not found']);
            exit;
        }

        $serverConfig = $servers[$serverNum];

        $cache = Cache::getInstance();
        $cacheKey = "logs_{$serverNum}_{$limit}";

        $result = $cache->remember($cacheKey, function () use ($serverConfig, $limit) {
            $client = new FreqtradeClient(
                $serverConfig['host'],
                $serverConfig['username'],
                $serverConfig['password']
            );

            $logs = $client->getLogs($limit);

            if ($logs === null) {
                $error = $client->getLastError();
                return [
                    'success' => false,
                    'error' => 'Failed to fetch logs: ' . ($error['message'] ?? 'Unknown error'),
                ];
            }

            return [
                'success' => true,
                'data' => $logs
            ];
        }, 10); // 10 seconds TTL for logs

        echo json_encode($result);
        exit;
    }

    // Main dashboard payload — protected: no valid session, no data.
    $requireAuth();

    $dashboard = new Dashboard();
    $serversData = $dashboard->fetchAllServers();
    $totals = $dashboard->getTotals();
    $lastTransactions = $dashboard->getLastTransactions(10);
    $dailyPerformance = $dashboard->getDailyPerformance(10);
    
    // Collect open trades
    $openTrades = [];
    foreach ($serversData as $server) {
        if ($server['online'] && $server['status'] && is_array($server['status'])) {
            foreach ($server['status'] as $trade) {
                $trade['_server_name'] = $server['name'];
                $openTrades[] = $trade;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'settings' => [
            'summary_enabled' => $config->isSummaryEnabled(),
            'sound_enabled' => $config->isSoundEnabled(),
            'coins_enabled' => $config->isCoinsEnabled(),
            'strategy_enabled' => $config->isStrategyEnabled(),
            'logs_enabled' => $config->isLogsEnabled(),
            'days' => $config->getDays(),
            'notify_duration' => $config->getNotifyDuration(),
        ],
        'data' => [
            'servers' => $serversData,
            'totals' => $totals,
            'transactions' => $lastTransactions,
            'daily' => $dailyPerformance,
            'open_trades' => $openTrades,
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
