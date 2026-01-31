<?php
/**
 * Debug script to test FreqTrade API responses
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/src/Config.php';
require_once __DIR__ . '/src/FreqtradeClient.php';

use FreqtradeDashboard\Config;
use FreqtradeDashboard\FreqtradeClient;

header('Content-Type: text/html; charset=utf-8');

echo "<html><head><title>FreqTrade API Debug</title>";
echo "<style>
body { background: #1a1a2e; color: #eee; font-family: monospace; padding: 20px; }
.server { background: #16213e; padding: 15px; margin: 10px 0; border-radius: 8px; }
.server h3 { color: #0f4c75; margin-top: 0; }
.success { color: #3fb950; }
.error { color: #f85149; }
.warning { color: #d29922; }
pre { background: #0d1117; padding: 10px; border-radius: 4px; overflow-x: auto; max-height: 300px; overflow-y: auto; }
.endpoint { margin: 10px 0; padding: 10px; background: #0d1117; border-left: 3px solid #30363d; }
.endpoint.ok { border-left-color: #3fb950; }
.endpoint.fail { border-left-color: #f85149; }
</style></head><body>";

echo "<h1>🔍 FreqTrade API Debug</h1>";

try {
    $config = Config::getInstance();
    $servers = $config->getServers();
    
    echo "<p>Found <strong>" . count($servers) . "</strong> servers in configuration.</p>";
    
    foreach ($servers as $num => $serverConfig) {
        echo "<div class='server'>";
        echo "<h3>Server #{$num}: {$serverConfig['name']}</h3>";
        echo "<p>Host: <code>{$serverConfig['host']}:{$serverConfig['port']}</code></p>";
        
        $client = new FreqtradeClient(
            $serverConfig['host'],
            $serverConfig['port'],
            $serverConfig['username'],
            $serverConfig['password']
        );
        
        // Test ping
        echo "<div class='endpoint'>";
        echo "<strong>1. Ping Test:</strong> ";
        if ($client->ping()) {
            echo "<span class='success'>✓ Server responding</span>";
        } else {
            echo "<span class='error'>✗ Server not responding</span>";
            echo "</div></div>";
            continue;
        }
        echo "</div>";
        
        // Test login
        echo "<div class='endpoint'>";
        echo "<strong>2. Authentication:</strong> ";
        if ($client->login()) {
            echo "<span class='success'>✓ Login successful</span>";
        } else {
            $error = $client->getLastError();
            echo "<span class='error'>✗ Login failed</span>";
            echo "<pre>" . print_r($error, true) . "</pre>";
            echo "</div></div>";
            continue;
        }
        echo "</div>";
        
        // Test each endpoint
        $endpoints = [
            'profit' => 'getProfit',
            'daily' => 'getDaily',
            'trades' => 'getTrades',
            'status' => 'getStatus',
            'balance' => 'getBalance',
            'config' => 'getConfig',
            'count' => 'getCount',
        ];
        
        foreach ($endpoints as $name => $method) {
            echo "<div class='endpoint'>";
            echo "<strong>API: /{$name}</strong><br>";
            
            $result = $client->$method();
            
            if ($result !== null) {
                echo "<span class='success'>✓ Response received</span>";
                echo "<pre>" . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
                
                // Check for expected fields
                if ($name === 'profit') {
                    $expectedFields = ['profit_closed_coin', 'closed_trade_count', 'winning_trades', 'losing_trades'];
                    $missing = [];
                    foreach ($expectedFields as $field) {
                        if (!isset($result[$field])) {
                            $missing[] = $field;
                        }
                    }
                    if (!empty($missing)) {
                        echo "<span class='warning'>⚠ Missing fields: " . implode(', ', $missing) . "</span><br>";
                    }
                    // Show first_trade info
                    echo "<br><strong>First Trade Info:</strong><br>";
                    echo "first_trade_timestamp: " . ($result['first_trade_timestamp'] ?? '<span class=\"warning\">NOT SET</span>') . "<br>";
                    echo "first_trade_humanized: " . ($result['first_trade_humanized'] ?? '<span class=\"warning\">NOT SET</span>') . "<br>";
                }
                
                if ($name === 'daily' && isset($result['data'])) {
                    echo "<span class='success'>✓ Daily data has " . count($result['data']) . " entries</span><br>";
                } elseif ($name === 'daily' && !isset($result['data'])) {
                    echo "<span class='warning'>⚠ Daily response has no 'data' key</span><br>";
                    echo "Keys found: " . implode(', ', array_keys($result));
                }
                
                if ($name === 'trades' && isset($result['trades'])) {
                    echo "<span class='success'>✓ Trades data has " . count($result['trades']) . " entries</span><br>";
                } elseif ($name === 'trades' && !isset($result['trades'])) {
                    echo "<span class='warning'>⚠ Trades response has no 'trades' key</span><br>";
                    echo "Keys found: " . implode(', ', array_keys($result));
                }
                
            } else {
                $error = $client->getLastError();
                echo "<span class='error'>✗ Request failed</span>";
                echo "<pre>" . print_r($error, true) . "</pre>";
            }
            echo "</div>";
        }
        
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "</body></html>";
