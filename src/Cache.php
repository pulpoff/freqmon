<?php

namespace FreqtradeDashboard;

/**
 * Simple file-based cache with TTL and file locking
 * Prevents multiple clients from overwhelming the FreqTrade API
 */
class Cache
{
    private static ?Cache $instance = null;
    private string $cacheDir;
    private int $ttl;

    private function __construct()
    {
        $this->cacheDir = __DIR__ . '/../cache';
        $this->ttl = Config::getInstance()->getCacheTTL();
        $this->ensureCacheDir();
    }

    public static function getInstance(): Cache
    {
        if (self::$instance === null) {
            self::$instance = new Cache();
        }
        return self::$instance;
    }

    /**
     * Ensure cache directory exists
     */
    private function ensureCacheDir(): void
    {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Get the file path for a cache key
     */
    private function getCacheFile(string $key): string
    {
        $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        return $this->cacheDir . '/' . $safeKey . '.cache';
    }

    /**
     * Get cached data if valid, or execute callback and cache result
     * Uses file locking to prevent thundering herd problem
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $ttl = $ttl ?? $this->ttl;
        $cacheFile = $this->getCacheFile($key);
        $lockFile = $cacheFile . '.lock';

        // Try to get from cache first (shared lock for reading)
        $cached = $this->getFromCache($cacheFile, $ttl);
        if ($cached !== null) {
            return $cached;
        }

        // Need to refresh - acquire exclusive lock
        $lockHandle = fopen($lockFile, 'c');
        if ($lockHandle === false) {
            // Can't get lock file, just execute callback
            return $callback();
        }

        // Try to get exclusive lock (non-blocking first)
        if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
            // Another process is refreshing, wait for it with blocking lock
            flock($lockHandle, LOCK_SH);
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);

            // Check cache again - it should be fresh now
            $cached = $this->getFromCache($cacheFile, $ttl);
            if ($cached !== null) {
                return $cached;
            }

            // Still no cache, execute callback (rare race condition)
            return $callback();
        }

        // We have exclusive lock - double-check cache wasn't updated
        $cached = $this->getFromCache($cacheFile, $ttl);
        if ($cached !== null) {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
            return $cached;
        }

        // Execute callback and cache result
        try {
            $result = $callback();
            $this->saveToCache($cacheFile, $result);
            return $result;
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
    }

    /**
     * Get data from cache file if it exists and is not expired
     */
    private function getFromCache(string $cacheFile, int $ttl): mixed
    {
        if (!file_exists($cacheFile)) {
            return null;
        }

        $mtime = filemtime($cacheFile);
        if ($mtime === false || (time() - $mtime) > $ttl) {
            return null;
        }

        $content = file_get_contents($cacheFile);
        if ($content === false) {
            return null;
        }

        $data = @unserialize($content);
        if ($data === false && $content !== serialize(false)) {
            return null;
        }

        return $data;
    }

    /**
     * Save data to cache file atomically
     */
    private function saveToCache(string $cacheFile, mixed $data): bool
    {
        $content = serialize($data);
        $tempFile = $cacheFile . '.tmp.' . getmypid();

        if (file_put_contents($tempFile, $content, LOCK_EX) === false) {
            return false;
        }

        // Atomic rename
        if (!rename($tempFile, $cacheFile)) {
            @unlink($tempFile);
            return false;
        }

        return true;
    }

    /**
     * Invalidate a specific cache key
     */
    public function forget(string $key): bool
    {
        $cacheFile = $this->getCacheFile($key);
        if (file_exists($cacheFile)) {
            return @unlink($cacheFile);
        }
        return true;
    }

    /**
     * Clear all cache files
     */
    public function flush(): int
    {
        $count = 0;
        $files = glob($this->cacheDir . '/*.cache');

        if ($files === false) {
            return 0;
        }

        foreach ($files as $file) {
            if (@unlink($file)) {
                $count++;
            }
        }

        // Also clean up lock files
        $lockFiles = glob($this->cacheDir . '/*.lock');
        if ($lockFiles !== false) {
            foreach ($lockFiles as $file) {
                @unlink($file);
            }
        }

        return $count;
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        $files = glob($this->cacheDir . '/*.cache');
        $stats = [
            'cache_dir' => $this->cacheDir,
            'ttl' => $this->ttl,
            'files' => 0,
            'total_size' => 0,
            'entries' => [],
        ];

        if ($files === false) {
            return $stats;
        }

        foreach ($files as $file) {
            $stats['files']++;
            $size = filesize($file);
            $mtime = filemtime($file);
            $age = time() - $mtime;
            $expired = $age > $this->ttl;

            $stats['total_size'] += $size;
            $stats['entries'][] = [
                'key' => basename($file, '.cache'),
                'size' => $size,
                'age' => $age,
                'expired' => $expired,
            ];
        }

        return $stats;
    }
}
