<?php

namespace FreqtradeDashboard;

class FreqtradeClient
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private ?string $accessToken = null;
    private int $timeout;
    private array $lastError = [];

    public function __construct(string $host, string $username, string $password, int $timeout = 10)
    {
        $this->baseUrl = rtrim($host, '/');
        if (!str_starts_with($this->baseUrl, 'http')) {
            $this->baseUrl = 'http://' . $this->baseUrl;
        }
        $this->username = $username;
        $this->password = $password;
        $this->timeout = $timeout;
    }

    /**
     * Authenticate and get JWT token
     */
    public function login(): bool
    {
        $url = $this->baseUrl . '/api/v1/token/login';
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_USERPWD => $this->username . ':' . $this->password,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->lastError = ['type' => 'curl', 'message' => $error];
            return false;
        }

        if ($httpCode !== 200) {
            $this->lastError = ['type' => 'http', 'code' => $httpCode, 'message' => $response];
            return false;
        }

        $data = json_decode($response, true);
        if (isset($data['access_token'])) {
            $this->accessToken = $data['access_token'];
            return true;
        }

        $this->lastError = ['type' => 'auth', 'message' => 'No access token in response'];
        return false;
    }

    /**
     * Make authenticated GET request
     */
    private function get(string $endpoint, array $params = []): ?array
    {
        if (!$this->accessToken && !$this->login()) {
            return null;
        }

        $url = $this->baseUrl . '/api/v1/' . ltrim($endpoint, '/');
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->lastError = ['type' => 'curl', 'message' => $error];
            return null;
        }

        if ($httpCode === 401) {
            // Token expired, try to re-login
            $this->accessToken = null;
            if ($this->login()) {
                return $this->get($endpoint, $params);
            }
            return null;
        }

        if ($httpCode !== 200) {
            $this->lastError = ['type' => 'http', 'code' => $httpCode, 'message' => $response];
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Simple ping to check if server is alive
     */
    public function ping(): bool
    {
        $url = $this->baseUrl . '/api/v1/ping';
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    /**
     * Get profit summary
     */
    public function getProfit(): ?array
    {
        return $this->get('profit');
    }

    /**
     * Get daily profit statistics
     */
    public function getDaily(int $days = 10): ?array
    {
        return $this->get('daily', ['timescale' => $days]);
    }

    /**
     * Get trade history
     */
    public function getTrades(int $limit = 10, int $offset = 0): ?array
    {
        return $this->get('trades', ['limit' => $limit, 'offset' => $offset]);
    }

    /**
     * Get open trades status
     */
    public function getStatus(): ?array
    {
        return $this->get('status');
    }

    /**
     * Get account balance
     */
    public function getBalance(): ?array
    {
        return $this->get('balance');
    }

    /**
     * Get bot configuration
     */
    public function getConfig(): ?array
    {
        return $this->get('show_config');
    }

    /**
     * Get performance by pair
     */
    public function getPerformance(): ?array
    {
        return $this->get('performance');
    }

    /**
     * Get statistics (durations, reasons)
     */
    public function getStats(): ?array
    {
        return $this->get('stats');
    }

    /**
     * Get trade count
     */
    public function getCount(): ?array
    {
        return $this->get('count');
    }

    /**
     * Get pair candles (OHLCV data)
     */
    public function getPairCandles(string $pair, string $timeframe = '5m', int $limit = 100): ?array
    {
        return $this->get('pair_candles', [
            'pair' => $pair,
            'timeframe' => $timeframe,
            'limit' => $limit
        ]);
    }

    /**
     * Get last error
     */
    public function getLastError(): array
    {
        return $this->lastError;
    }

    /**
     * Get all dashboard data in one call
     */
    public function getDashboardData(): array
    {
        $data = [
            'online' => false,
            'profit' => null,
            'daily' => null,
            'trades' => null,
            'status' => null,
            'balance' => null,
            'config' => null,
            'count' => null,
            'error' => null,
        ];

        if (!$this->ping()) {
            $data['error'] = 'Server offline';
            return $data;
        }

        $data['online'] = true;

        if (!$this->login()) {
            $data['error'] = 'Authentication failed: ' . ($this->lastError['message'] ?? 'Unknown error');
            return $data;
        }

        $data['profit'] = $this->getProfit();
        $days = Config::getInstance()->getDays();
        $data['daily'] = $this->getDaily($days);
        $data['count'] = $this->getCount();

        // Fetch limited trades (100 max) and filter to essential fields only
        $trades = $this->getTrades(100);
        $data['trades'] = $this->filterTradeFields($trades);

        $data['status'] = $this->getStatus();
        $data['balance'] = $this->getBalance();

        // Filter config to only essential fields
        $fullConfig = $this->getConfig();
        $data['config'] = $this->filterConfigFields($fullConfig);

        return $data;
    }

    /**
     * Filter trades to only essential fields to reduce data transfer
     */
    private function filterTradeFields(?array $tradesData): ?array
    {
        if ($tradesData === null || !isset($tradesData['trades'])) {
            return $tradesData;
        }

        $essentialFields = [
            'trade_id', 'pair', 'is_open', 'is_short',
            'open_date', 'close_date',
            'profit_abs', 'profit_pct', 'profit_ratio',
            'stake_amount', 'amount', 'open_rate', 'close_rate',
            'stop_loss_abs', 'stop_loss_pct',
            'realized_profit',
        ];

        $filteredTrades = [];
        foreach ($tradesData['trades'] as $trade) {
            $filtered = [];
            foreach ($essentialFields as $field) {
                if (isset($trade[$field])) {
                    $filtered[$field] = $trade[$field];
                }
            }
            $filteredTrades[] = $filtered;
        }

        return ['trades' => $filteredTrades];
    }

    /**
     * Filter config to only essential fields to reduce data transfer
     */
    private function filterConfigFields(?array $config): ?array
    {
        if ($config === null) {
            return null;
        }

        $essentialFields = [
            'strategy', 'stake_currency', 'dry_run',
            'trading_mode', 'timeframe',
        ];

        $filtered = [];
        foreach ($essentialFields as $field) {
            if (isset($config[$field])) {
                $filtered[$field] = $config[$field];
            }
        }

        return $filtered;
    }
}
