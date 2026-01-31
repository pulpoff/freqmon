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
     * Fetch data from all servers
     */
    public function fetchAllServers(): array
    {
        $servers = $this->config->getServers();
        $results = [];

        foreach ($servers as $num => $serverConfig) {
            $client = new FreqtradeClient(
                $serverConfig['host'],
                $serverConfig['username'],
                $serverConfig['password']
            );

            $data = $client->getDashboardData();
            $data['name'] = $serverConfig['name'];
            $data['host'] = $serverConfig['host'];
            $data['server_num'] = $num;

            $results[$num] = $data;
        }

        $this->serversData = $results;
        return $results;
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
