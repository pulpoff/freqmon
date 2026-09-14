<?php

namespace FreqtradeDashboard;

/**
 * Brute-force protection for the dashboard password.
 *
 * Tracks failed password attempts per client host in a small JSON file and
 * bans a host for a configurable time after too many failures. Configured via
 * GUARD=<tries>,<hours> in .env (e.g. "3,3" = ban for 3 hours after 3 failed
 * tries). All read-modify-write access is serialised with an exclusive lock so
 * concurrent requests can't corrupt the counters.
 */
class LoginGuard
{
    private string $file;
    private int $maxFails;
    private int $banSeconds;
    private float $minInterval;

    public function __construct(int $maxFails = 3, int $banSeconds = 10800, float $minInterval = 2.0, ?string $file = null)
    {
        $this->maxFails = max(1, $maxFails);
        $this->banSeconds = max(1, $banSeconds);
        $this->minInterval = max(0.0, $minInterval);
        $this->file = $file ?? (__DIR__ . '/../cache/login_bans.json');
    }

    /**
     * Best-effort client IP. X-Forwarded-For is only trusted when the direct
     * peer is a local/private address (i.e. a reverse proxy on the same host);
     * otherwise an external client could spoof the header to dodge or forge bans.
     */
    public function getClientIp(): string
    {
        $remote = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if ($this->isTrustedProxy($remote) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
            $client = $parts[0] ?? '';
            if (filter_var($client, FILTER_VALIDATE_IP)) {
                return $client;
            }
        }
        return $remote;
    }

    private function isTrustedProxy(string $ip): bool
    {
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return true;
        }
        // A private/reserved address fails this filter (returns false) => local proxy.
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    /**
     * Whether the host is currently banned.
     *
     * @return array{banned: bool, retry_after: int}
     */
    public function check(string $ip): array
    {
        $all = $this->readAll();
        $now = time();
        $e = $all[$ip] ?? null;
        if (is_array($e) && ($e['until'] ?? 0) > $now) {
            return ['banned' => true, 'retry_after' => (int) $e['until'] - $now];
        }
        return ['banned' => false, 'retry_after' => 0];
    }

    /**
     * Rate-limit attempts: reject if the host tried again within minInterval.
     * Records the attempt time on every call so rapid hammering stays throttled.
     *
     * @return array{throttled: bool, retry_after: int}
     */
    public function throttle(string $ip): array
    {
        if ($this->minInterval <= 0) {
            return ['throttled' => false, 'retry_after' => 0];
        }
        return $this->withLock(function (array &$all) use ($ip) {
            $now = microtime(true);
            $e = $all[$ip] ?? [];
            if (!is_array($e)) {
                $e = [];
            }
            $lastAttempt = (float) ($e['ts'] ?? 0);
            $e['ts'] = $now;
            $all[$ip] = $e;
            $wait = $this->minInterval - ($now - $lastAttempt);
            if ($lastAttempt > 0 && $wait > 0) {
                return ['throttled' => true, 'retry_after' => (int) max(1, ceil($wait))];
            }
            return ['throttled' => false, 'retry_after' => 0];
        });
    }

    /**
     * Record a failed attempt, applying a ban once the limit is reached.
     *
     * @return array{banned: bool, retry_after: int, remaining: int}
     */
    public function recordFailure(string $ip): array
    {
        return $this->withLock(function (array &$all) use ($ip) {
            $now = time();
            $e = $all[$ip] ?? null;

            // Already banned: leave the timer untouched.
            if (is_array($e) && ($e['until'] ?? 0) > $now) {
                return ['banned' => true, 'retry_after' => (int) $e['until'] - $now, 'remaining' => 0];
            }
            // Fresh window (new host, or a previous ban has since expired).
            if (!is_array($e) || ($e['until'] ?? 0) !== 0) {
                $e = ['fails' => 0, 'first' => $now, 'until' => 0];
            }

            $e['fails'] = (int) ($e['fails'] ?? 0) + 1;
            $e['last'] = $now;
            if ($e['fails'] >= $this->maxFails) {
                $e['until'] = $now + $this->banSeconds;
            }
            $all[$ip] = $e;

            $banned = ($e['until'] ?? 0) > $now;
            return [
                'banned' => $banned,
                'retry_after' => $banned ? (int) $e['until'] - $now : 0,
                'remaining' => max(0, $this->maxFails - $e['fails']),
            ];
        });
    }

    /** Clear a host's record after a successful login. */
    public function recordSuccess(string $ip): void
    {
        $this->withLock(function (array &$all) use ($ip) {
            unset($all[$ip]);
            return null;
        });
    }

    private function withLock(callable $fn)
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $fp = @fopen($this->file, 'c+');
        if ($fp === false) {
            // Can't lock: fall back to a lock-free read/modify/write.
            $all = $this->readAll();
            $result = $fn($all);
            $this->writeAll($all);
            return $result;
        }
        @flock($fp, LOCK_EX);
        $raw = stream_get_contents($fp);
        $all = json_decode($raw ?: '[]', true);
        if (!is_array($all)) {
            $all = [];
        }
        $result = $fn($all);
        $all = $this->prune($all);
        rewind($fp);
        ftruncate($fp, 0);
        fwrite($fp, json_encode($all));
        fflush($fp);
        @flock($fp, LOCK_UN);
        fclose($fp);
        return $result;
    }

    /** Drop stale records: expired bans whose last activity is well past. */
    private function prune(array $all): array
    {
        $now = time();
        foreach ($all as $ip => $e) {
            if (!is_array($e)) {
                unset($all[$ip]);
                continue;
            }
            $until = (int) ($e['until'] ?? 0);
            $last = (int) max((float) ($e['last'] ?? 0), (float) ($e['first'] ?? 0), (float) ($e['ts'] ?? 0));
            if ($until <= $now && $last < $now - $this->banSeconds) {
                unset($all[$ip]);
            }
        }
        return $all;
    }

    private function readAll(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $raw = @file_get_contents($this->file);
        $data = json_decode($raw ?: '[]', true);
        return is_array($data) ? $data : [];
    }

    private function writeAll(array $all): void
    {
        @file_put_contents($this->file, json_encode($this->prune($all)), LOCK_EX);
    }
}
