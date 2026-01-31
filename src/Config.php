<?php

namespace FreqtradeDashboard;

class Config
{
    private static ?Config $instance = null;
    private array $servers = [];
    private array $settings = [];

    private function __construct()
    {
        $this->loadEnv();
    }

    public static function getInstance(): Config
    {
        if (self::$instance === null) {
            self::$instance = new Config();
        }
        return self::$instance;
    }

    private function loadEnv(): void
    {
        $envFile = __DIR__ . '/../.env';
        
        if (!file_exists($envFile)) {
            throw new \RuntimeException('.env file not found. Please copy .env.example to .env and configure your servers.');
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip comments
            if (str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Parse server entries
            if (preg_match('/^SERVER_(\d+)$/', $key, $matches)) {
                $serverNum = (int) $matches[1];
                $parts = explode('|', $value);
                
                if (count($parts) >= 4) {
                    $this->servers[$serverNum] = [
                        'name' => $parts[0],
                        'host' => $parts[1],
                        'username' => $parts[2],
                        'password' => $parts[3],
                    ];
                }
            } else {
                // Other settings
                $this->settings[$key] = $value;
            }
        }

        // Sort servers by number
        ksort($this->servers);
    }

    public function getServers(): array
    {
        return $this->servers;
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    public function getRefreshInterval(): int
    {
        return (int) $this->getSetting('REFRESH_INTERVAL', 60);
    }

    public function getCacheTTL(): int
    {
        return (int) $this->getSetting('CACHE_TTL', 30);
    }

    public function getTimezone(): string
    {
        return $this->getSetting('TIMEZONE', 'UTC');
    }
}
