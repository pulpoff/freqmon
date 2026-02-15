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
require_once __DIR__ . '/src/Dashboard.php';

use FreqtradeDashboard\Config;
use FreqtradeDashboard\Cache;
use FreqtradeDashboard\Dashboard;
use FreqtradeDashboard\FreqtradeClient;

try {
    $config = Config::getInstance();
    date_default_timezone_set($config->getTimezone());

    $action = isset($_GET['action']) ? $_GET['action'] : '';

    if ($action === 'check_auth') {
        $password = $config->getPassword();
        echo json_encode([
            'success' => true,
            'auth_required' => $password !== null,
        ]);
        exit;
    }

    if ($action === 'verify_password') {
        $input = json_decode(file_get_contents('php://input'), true);
        $providedPassword = $input['password'] ?? '';
        $configPassword = $config->getPassword();

        if ($configPassword === null || $providedPassword === $configPassword) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid password']);
        }
        exit;
    }

    // Handle pair_candles request
    if ($action === 'pair_candles') {
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
