<?php

namespace FreqtradeDashboard;

class Dashboard
{
    private Config $config;
    private array $serversData = [];

    public function __construct()
    {
        $this->config = Config::getInstance();
    }

    /**
     * Fetch data from all servers with caching
     * Uses file-based cache to prevent multiple clients from overwhelming the API
     */
    public function fetchAllServers(bool $useCache = true): array
    {
        if (!$useCache) {
            return $this->fetchAllServersUncached();
        }

        $cache = Cache::getInstance();
        $this->serversData = $cache->remember('dashboard_servers', function () {
            return $this->fetchAllServersUncached();
        });

        return $this->serversData;
    }

    /**
     * Fetch data from all servers without caching (internal use)
     */
    private function fetchAllServersUncached(): array
    {
        $servers = $this->config->getServers();
        $concurrency = $this->config->getParallelFetch();

        if (count($servers) <= 1 || $concurrency <= 1 || !$this->canFork()) {
            return $this->fetchServersSequential($servers);
        }

        return $this->fetchServersParallel($servers, $concurrency);
    }

    private function canFork(): bool
    {
        return function_exists('pcntl_fork')
            && function_exists('pcntl_waitpid')
            && function_exists('pcntl_signal');
    }

    private function fetchServersSequential(array $servers): array
    {
        $results = [];
        foreach ($servers as $num => $serverConfig) {
            $results[$num] = $this->fetchOneServer($num, $serverConfig);
        }
        return $results;
    }

    private function fetchOneServer(int $num, array $serverConfig): array
    {
        $client = new FreqtradeClient(
            $serverConfig['host'],
            $serverConfig['username'],
            $serverConfig['password']
        );

        $data = $client->getDashboardData();
        $data['name'] = $serverConfig['name'];
        $data['host'] = $serverConfig['host'];
        $data['server_num'] = $num;
        $data['url'] = $serverConfig['url'] ?? null;
        return $data;
    }

    /**
     * Fetch servers concurrently using pcntl_fork, with file-based IPC.
     * Limits the number of simultaneous children to $maxConcurrency.
     */
    private function fetchServersParallel(array $servers, int $maxConcurrency): array
    {
        $tmpDir = sys_get_temp_dir() . '/freqmon_' . bin2hex(random_bytes(6));
        if (!@mkdir($tmpDir, 0700, true)) {
            return $this->fetchServersSequential($servers);
        }

        $results = [];
        $pending = [];
        foreach ($servers as $num => $serverConfig) {
            $pending[] = [$num, $serverConfig];
        }
        $running = []; // pid => num

        try {
            while (!empty($pending) || !empty($running)) {
                while (count($running) < $maxConcurrency && !empty($pending)) {
                    [$num, $serverConfig] = array_shift($pending);
                    $pid = @pcntl_fork();

                    if ($pid === -1) {
                        // Fork failed: run this one inline and continue
                        $results[$num] = $this->fetchOneServer($num, $serverConfig);
                        continue;
                    }

                    if ($pid === 0) {
                        $this->runChild($num, $serverConfig, $tmpDir);
                    }

                    $running[$pid] = $num;
                }

                if (!empty($running)) {
                    $status = 0;
                    $pid = pcntl_waitpid(-1, $status);
                    if ($pid <= 0) {
                        break;
                    }
                    if (!isset($running[$pid])) {
                        continue;
                    }
                    $num = $running[$pid];
                    unset($running[$pid]);
                    $results[$num] = $this->readChildResult($tmpDir, $num, $servers[$num]);
                }
            }
        } finally {
            $this->cleanupDir($tmpDir);
        }

        // Preserve original ordering
        $ordered = [];
        foreach ($servers as $num => $_) {
            if (isset($results[$num])) {
                $ordered[$num] = $results[$num];
            }
        }
        return $ordered;
    }

    /**
     * Child process entry point: fetches one server, writes serialized result, exits.
     * Never returns.
     */
    private function runChild(int $num, array $serverConfig, string $tmpDir): void
    {
        // Don't let warnings leak into the HTTP response stream shared with parent
        ini_set('display_errors', '0');
        $target = $tmpDir . '/' . $num . '.ser';
        try {
            $data = $this->fetchOneServer($num, $serverConfig);
            @file_put_contents($target, serialize($data));
        } catch (\Throwable $e) {
            @file_put_contents($tmpDir . '/' . $num . '.err', $e->getMessage());
        }
        exit(0);
    }

    private function readChildResult(string $tmpDir, int $num, array $serverConfig): array
    {
        $file = $tmpDir . '/' . $num . '.ser';
        if (is_file($file)) {
            $raw = @file_get_contents($file);
            @unlink($file);
            if ($raw !== false) {
                $data = @unserialize($raw);
                if (is_array($data)) {
                    return $data;
                }
            }
        }

        $errFile = $tmpDir . '/' . $num . '.err';
        $error = 'Parallel fetch failed';
        if (is_file($errFile)) {
            $error = @file_get_contents($errFile) ?: $error;
            @unlink($errFile);
        }

        return [
            'online' => false,
            'profit' => null,
            'daily' => null,
            'trades' => null,
            'status' => null,
            'balance' => null,
            'config' => null,
            'count' => null,
            'performance' => null,
            'whitelist' => null,
            'error' => $error,
            'name' => $serverConfig['name'],
            'host' => $serverConfig['host'],
            'server_num' => $num,
            'url' => $serverConfig['url'] ?? null,
        ];
    }

    private function cleanupDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $entries = glob($dir . '/*') ?: [];
        foreach ($entries as $entry) {
            @unlink($entry);
        }
        @rmdir($dir);
    }

    /**
     * Get aggregated totals across all servers
     */
    public function getTotals(): array
    {
        $totals = [
            'servers_online' => 0,
            'servers_total' => count($this->serversData),
            'total_profit' => 0,
            'total_profit_percent' => 0,
            'total_trades' => 0,
            'total_trades_closed' => 0,
            'total_open_trades' => 0,
            'winning_trades' => 0,
            'losing_trades' => 0,
            'total_stake' => 0,
            'currencies' => [],
        ];

        $profitPercentages = [];

        foreach ($this->serversData as $data) {
            if ($data['online']) {
                $totals['servers_online']++;
            }

            if ($data['profit']) {
                $profit = $data['profit'];
                $totals['total_profit'] += $profit['profit_closed_coin'] ?? 0;
                $totals['total_trades_closed'] += $profit['closed_trade_count'] ?? 0;
                $totals['winning_trades'] += $profit['winning_trades'] ?? 0;
                $totals['losing_trades'] += $profit['losing_trades'] ?? 0;
                
                if (isset($profit['profit_closed_percent'])) {
                    $profitPercentages[] = $profit['profit_closed_percent'];
                }
            }

            if ($data['status'] && is_array($data['status'])) {
                $totals['total_open_trades'] += count($data['status']);
            }

            if ($data['balance'] && isset($data['balance']['total'])) {
                $totals['total_stake'] += $data['balance']['total'];
            }

            if ($data['config'] && isset($data['config']['stake_currency'])) {
                $currency = $data['config']['stake_currency'];
                if (!in_array($currency, $totals['currencies'])) {
                    $totals['currencies'][] = $currency;
                }
            }
        }

        // Calculate average profit percentage
        if (!empty($profitPercentages)) {
            $totals['total_profit_percent'] = array_sum($profitPercentages) / count($profitPercentages);
        }

        $totals['total_trades'] = $totals['total_trades_closed'] + $totals['total_open_trades'];
        
        // Win rate
        $totalClosed = $totals['winning_trades'] + $totals['losing_trades'];
        $totals['win_rate'] = $totalClosed > 0 
            ? round(($totals['winning_trades'] / $totalClosed) * 100, 1) 
            : 0;

        return $totals;
    }

    /**
     * Get combined last 10 transactions across all servers
     */
    public function getLastTransactions(int $limit = 10): array
    {
        $allTrades = [];

        foreach ($this->serversData as $serverNum => $data) {
            if (!$data['online'] || !$data['trades'] || !isset($data['trades']['trades'])) {
                continue;
            }

            foreach ($data['trades']['trades'] as $trade) {
                $trade['_server_name'] = $data['name'];
                $trade['_server_num'] = $serverNum;
                $allTrades[] = $trade;
            }
        }

        // Sort by close_date descending
        usort($allTrades, function ($a, $b) {
            $dateA = $a['close_date'] ?? $a['open_date'] ?? '1970-01-01';
            $dateB = $b['close_date'] ?? $b['open_date'] ?? '1970-01-01';
            return strtotime($dateB) - strtotime($dateA);
        });

        return array_slice($allTrades, 0, $limit);
    }

    /**
     * Get combined daily performance for chart
     */
    public function getDailyPerformance(int $days = 10): array
    {
        $dailyData = [];

        foreach ($this->serversData as $serverNum => $data) {
            if (!$data['online'] || !$data['daily'] || !isset($data['daily']['data'])) {
                continue;
            }

            foreach ($data['daily']['data'] as $day) {
                $date = $day['date'] ?? null;
                if (!$date) continue;

                if (!isset($dailyData[$date])) {
                    $dailyData[$date] = [
                        'date' => $date,
                        'profit' => 0,
                        'trades' => 0,
                    ];
                }

                $dailyData[$date]['profit'] += $day['abs_profit'] ?? 0;
                $dailyData[$date]['trades'] += $day['trade_count'] ?? 0;
            }
        }

        // Sort by date
        ksort($dailyData);

        // Take last N days
        $dailyData = array_slice($dailyData, -$days, $days, true);

        return array_values($dailyData);
    }

    /**
     * Get servers data
     */
    public function getServersData(): array
    {
        return $this->serversData;
    }
}
