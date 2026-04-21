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
     * Fetch data from all servers without caching (internal use).
     *
     * Runs three parallel batches via curl_multi:
     *   1. ping all servers
     *   2. login for servers that replied to ping
     *   3. fetch all data endpoints (profit, daily, count, status, balance,
     *      performance, whitelist, show_config) across all authed servers
     *   4. fetch trades with a size limit derived from profit.closed_trade_count
     */
    private function fetchAllServersUncached(): array
    {
        $servers = $this->config->getServers();
        if (empty($servers)) {
            return [];
        }

        $concurrency = $this->config->getParallelFetch();
        $days = $this->config->getDays();

        $results = [];
        foreach ($servers as $num => $cfg) {
            $results[$num] = $this->emptyServerData($num, $cfg);
        }

        // Phase 1: ping
        $pingReqs = [];
        foreach ($servers as $num => $cfg) {
            $pingReqs[] = [
                'id' => $num,
                'url' => $this->baseUrl($cfg['host']) . '/api/v1/ping',
                'method' => 'GET',
                'timeout' => 5,
                'connect_timeout' => 3,
            ];
        }
        $pingResp = ParallelHttp::run($pingReqs, $concurrency);
        foreach ($servers as $num => $cfg) {
            $r = $pingResp[$num] ?? null;
            if ($r && $r['status'] === 200) {
                $results[$num]['online'] = true;
            } else {
                $results[$num]['error'] = 'Server offline';
            }
        }

        // Phase 2: login
        $loginReqs = [];
        foreach ($servers as $num => $cfg) {
            if (!$results[$num]['online']) continue;
            $loginReqs[] = [
                'id' => $num,
                'url' => $this->baseUrl($cfg['host']) . '/api/v1/token/login',
                'method' => 'POST',
                'userpwd' => $cfg['username'] . ':' . $cfg['password'],
                'headers' => ['Content-Type: application/json'],
                'timeout' => 10,
                'connect_timeout' => 5,
            ];
        }
        $loginResp = ParallelHttp::run($loginReqs, $concurrency);

        $tokens = [];
        foreach ($loginReqs as $req) {
            $num = $req['id'];
            $r = $loginResp[$num] ?? null;
            if ($r && $r['status'] === 200 && !empty($r['body'])) {
                $json = json_decode($r['body'], true);
                if (is_array($json) && isset($json['access_token'])) {
                    $tokens[$num] = $json['access_token'];
                    continue;
                }
            }
            $msg = $r['error'] ?? ('HTTP ' . ($r['status'] ?? 0));
            $results[$num]['error'] = 'Authentication failed: ' . $msg;
        }

        // Phase 3: data endpoints (each server gets the same set of reads)
        $endpoints = [
            'profit'      => ['path' => 'profit'],
            'daily'       => ['path' => 'daily', 'query' => ['timescale' => $days]],
            'count'       => ['path' => 'count'],
            'status'      => ['path' => 'status'],
            'balance'     => ['path' => 'balance'],
            'performance' => ['path' => 'performance'],
            'whitelist'   => ['path' => 'whitelist'],
            'config'      => ['path' => 'show_config'],
        ];

        $dataReqs = [];
        foreach ($tokens as $num => $token) {
            $base = $this->baseUrl($servers[$num]['host']) . '/api/v1/';
            $headers = ['Authorization: Bearer ' . $token, 'Content-Type: application/json'];
            foreach ($endpoints as $key => $ep) {
                $url = $base . $ep['path'];
                if (!empty($ep['query'])) {
                    $url .= '?' . http_build_query($ep['query']);
                }
                $dataReqs[] = [
                    'id' => $num . '|' . $key,
                    'url' => $url,
                    'method' => 'GET',
                    'headers' => $headers,
                    'timeout' => 10,
                    'connect_timeout' => 5,
                ];
            }
        }
        $dataResp = ParallelHttp::run($dataReqs, $concurrency);

        foreach ($tokens as $num => $_) {
            foreach ($endpoints as $key => $_ep) {
                $r = $dataResp[$num . '|' . $key] ?? null;
                if (!$r || $r['status'] !== 200 || !is_string($r['body'])) {
                    continue;
                }
                $json = json_decode($r['body'], true);
                if ($key === 'config') {
                    $json = FreqtradeClient::filterConfigFieldsStatic($json);
                }
                $results[$num][$key] = $json;
            }
        }

        // Phase 4: trades (limit depends on profit.closed_trade_count)
        $tradeReqs = [];
        foreach ($tokens as $num => $token) {
            $profit = $results[$num]['profit'] ?? null;
            $limit = 50;
            if (is_array($profit) && isset($profit['closed_trade_count'])) {
                $limit = max(50, (int) $profit['closed_trade_count'] + 20);
            }
            $url = $this->baseUrl($servers[$num]['host']) . '/api/v1/trades?'
                . http_build_query(['limit' => $limit, 'offset' => 0]);
            $tradeReqs[] = [
                'id' => $num,
                'url' => $url,
                'method' => 'GET',
                'headers' => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
                'timeout' => 15,
                'connect_timeout' => 5,
            ];
        }
        $tradeResp = ParallelHttp::run($tradeReqs, $concurrency);
        foreach ($tradeResp as $num => $r) {
            if ($r['status'] === 200 && !empty($r['body'])) {
                $json = json_decode($r['body'], true);
                $results[$num]['trades'] = FreqtradeClient::filterTradeFieldsStatic(is_array($json) ? $json : null);
            }
        }

        return $results;
    }

    private function baseUrl(string $host): string
    {
        $host = rtrim($host, '/');
        if (!str_starts_with($host, 'http://') && !str_starts_with($host, 'https://')) {
            $host = 'http://' . $host;
        }
        return $host;
    }

    private function emptyServerData(int $num, array $cfg): array
    {
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
            'error' => null,
            'name' => $cfg['name'],
            'host' => $cfg['host'],
            'server_num' => $num,
            'url' => $cfg['url'] ?? null,
        ];
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
