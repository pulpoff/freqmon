<?php

namespace FreqtradeDashboard;

/**
 * Server-side session auth for the dashboard.
 *
 * On a correct password we mint a cryptographically-random opaque token, store
 * only its SHA-256 hash server-side (so a leak of the store can't be replayed),
 * and hand the raw token to the browser as an HttpOnly, SameSite=Strict cookie.
 * Every data endpoint then validates that cookie server-side — the password
 * itself is never stored in the browser and the data is never served without a
 * valid session. Tokens expire after a TTL and are pruned on write.
 */
class AuthSession
{
    private string $file;
    private int $ttl;
    private string $cookieName = 'freqmon_session';

    public function __construct(int $ttlSeconds = 2592000, ?string $file = null)
    {
        $this->ttl = max(60, $ttlSeconds);
        $this->file = $file ?? (__DIR__ . '/../cache/sessions.json');
    }

    public function currentToken(): ?string
    {
        $t = $_COOKIE[$this->cookieName] ?? null;
        return (is_string($t) && $t !== '') ? $t : null;
    }

    public function isAuthenticated(): bool
    {
        $token = $this->currentToken();
        if ($token === null) {
            return false;
        }
        $all = $this->readAll();
        $hash = hash('sha256', $token);
        $exp = $all[$hash] ?? 0;
        return is_numeric($exp) && (int) $exp > time();
    }

    /** Mint a new session, persist its hash, and set the cookie. */
    public function issue(): void
    {
        $token = bin2hex(random_bytes(32));
        $expires = time() + $this->ttl;
        $this->withLock(function (array &$all) use ($token, $expires) {
            $all[hash('sha256', $token)] = $expires;
            return null;
        });
        $this->setCookie($token, $expires);
    }

    /** Invalidate the current session (server-side and cookie). */
    public function revoke(): void
    {
        $token = $this->currentToken();
        if ($token !== null) {
            $this->withLock(function (array &$all) use ($token) {
                unset($all[hash('sha256', $token)]);
                return null;
            });
        }
        $this->setCookie('', time() - 3600);
    }

    private function setCookie(string $value, int $expires): void
    {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        // headers are buffered (ob_start), so this is safe even after some output
        setcookie($this->cookieName, $value, [
            'expires' => $expires,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Strict',
            'secure' => $secure,
        ]);
    }

    private function withLock(callable $fn)
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $fp = @fopen($this->file, 'c+');
        if ($fp === false) {
            $all = $this->readAll();
            $result = $fn($all);
            @file_put_contents($this->file, json_encode($this->prune($all)), LOCK_EX);
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

    private function prune(array $all): array
    {
        $now = time();
        foreach ($all as $hash => $exp) {
            if (!is_numeric($exp) || (int) $exp <= $now) {
                unset($all[$hash]);
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
}
