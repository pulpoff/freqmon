<?php
/**
 * API endpoint for fetching dashboard data via AJAX
 * Returns JSON response for real-time updates without page reload
 */

// Ensure clean output
ob_start();

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/src/Config.php';
require_once __DIR__ . '/src/FreqtradeClient.php';
require_once __DIR__ . '/src/Dashboard.php';

use FreqtradeDashboard\Config;
use FreqtradeDashboard\Dashboard;
use FreqtradeDashboard\FreqtradeClient;

try {
    $config = Config::getInstance();
    date_default_timezone_set($config->getTimezone());
    
    // Handle pair_candles request
    if (isset($_GET['action']) && $_GET['action'] === 'pair_candles') {
        ob_clean(); // Clear any previous output
        
        $serverNum = isset($_GET['server']) ? intval($_GET['server']) : 1;
        $pair = isset($_GET['pair']) ? $_GET['pair'] : '';
        $timeframe = isset($_GET['timeframe']) ? $_GET['timeframe'] : '5m';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
        
        if (empty($pair)) {
            echo json_encode(['success' => false, 'error' => 'Pair is required']);
            exit;
        }
        
        $servers = $config->getServers();
        if (!isset($servers[$serverNum])) {
            echo json_encode(['success' => false, 'error' => 'Server not found: ' . $serverNum . '. Available: ' . implode(', ', array_keys($servers))]);
            exit;
        }
        
        $serverConfig = $servers[$serverNum];
        $client = new FreqtradeClient(
            $serverConfig['host'],
            $serverConfig['username'],
            $serverConfig['password']
        );
        
        // Get pair candles from FreqTrade
        $candles = $client->getPairCandles($pair, $timeframe, $limit);
        
        if ($candles === null) {
            $error = $client->getLastError();
            echo json_encode([
                'success' => false, 
                'error' => 'Failed to fetch candles: ' . ($error['message'] ?? 'Unknown error'),
                'debug' => $error
            ]);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $candles
        ]);
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
