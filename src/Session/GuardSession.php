<?php
declare(strict_types=1);

namespace TwoIzi\Guard\Session;

use TwoIzi\Guard\Config\Config;
use TwoIzi\Guard\Security\Crypto;
use TwoIzi\Guard\Storage\Database;
use TwoIzi\Guard\Support\Encoding;

final class GuardSession
{
    private ?string $secret = null;
    private ?string $hash = null;

    public function __construct(private Config $config, private Database $db) {}

    public function ensure(): string
    {
        if ($this->hash !== null) {
            return $this->hash;
        }
        $name = (string)$this->config->get('cookie.name', '__Host-izi_guard');
        $rawCookie = $_COOKIE[$name] ?? '';
        if (is_string($rawCookie) && strlen($rawCookie) <= 64 && str_starts_with($rawCookie, 'gs_')) {
            try {
                $candidate = Encoding::base64UrlDecode(substr($rawCookie, 3));
                if (strlen($candidate) === 32) {
                    $this->secret = $candidate;
                }
            } catch (\Throwable) {
                $this->secret = null;
            }
        }
        if ($this->secret === null) {
            $this->secret = random_bytes(32);
            $cookie = 'gs_' . Encoding::base64UrlEncode($this->secret);
            $ttl = (int)$this->config->get('cookie.ttl', 7200);
            setcookie($name, $cookie, [
                'expires' => time() + $ttl,
                'path' => '/',
                'secure' => (bool)$this->config->get('cookie.secure', true),
                'httponly' => true,
                'samesite' => (string)$this->config->get('cookie.samesite', 'Lax'),
            ]);
            $_COOKIE[$name] = $cookie;
        }
        $this->hash = Crypto::sha256($this->secret);
        $this->touch();
        return $this->hash;
    }

    public function hash(): string
    {
        return $this->ensure();
    }

    public function stats(): array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT successful_actions, failed_actions, successful_challenges, failed_challenges, pow_ema_ms, last_pow_difficulty, '
          . 'GREATEST(0, TIMESTAMPDIFF(SECOND, created_at, NOW(6))) AS age_seconds '
          . 'FROM guard_sessions WHERE session_hash = ? LIMIT 1'
        );
        $stmt->execute([$this->hash()]);
        return $stmt->fetch() ?: [
            'successful_actions' => 0,
            'failed_actions' => 0,
            'successful_challenges' => 0,
            'failed_challenges' => 0,
            'pow_ema_ms' => null,
            'last_pow_difficulty' => null,
            'age_seconds' => 0,
        ];
    }

    public function calibratedDifficulty(int $floor, int $max, int $targetMs): int
    {
        $stats = $this->stats();
        $ema = $stats['pow_ema_ms'] !== null ? (float)$stats['pow_ema_ms'] : null;
        $last = $stats['last_pow_difficulty'] !== null ? (int)$stats['last_pow_difficulty'] : null;
        if ($ema === null || $last === null) {
            return $floor;
        }
        $candidate = max($floor, min($max, $last));
        if ($ema < max(50, $targetMs * 0.55)) {
            $candidate++;
        } elseif ($ema > $targetMs * 1.8) {
            $candidate--;
        }
        // Server-measured calibration may change friction but never below the risk-derived floor.
        return max($floor, min($max, $candidate));
    }

    public function recordPow(int $solveMs, int $difficulty, bool $success): void
    {
        if ($success) {
            $stmt = $this->db->pdo()->prepare(
                'UPDATE guard_sessions SET successful_challenges = successful_challenges + 1, '
              . 'pow_ema_ms = CASE WHEN pow_ema_ms IS NULL THEN ? ELSE (pow_ema_ms * 0.75 + ? * 0.25) END, '
              . 'last_pow_difficulty = ? WHERE session_hash = ?'
            );
            $stmt->execute([$solveMs, $solveMs, $difficulty, $this->hash()]);
            return;
        }
        $stmt = $this->db->pdo()->prepare('UPDATE guard_sessions SET failed_challenges = failed_challenges + 1 WHERE session_hash = ?');
        $stmt->execute([$this->hash()]);
    }

    public function recordActionResult(bool $success): void
    {
        $column = $success ? 'successful_actions' : 'failed_actions';
        $stmt = $this->db->pdo()->prepare("UPDATE guard_sessions SET {$column} = {$column} + 1 WHERE session_hash = ?");
        $stmt->execute([$this->hash()]);
    }

    private function touch(): void
    {
        $ttl = (int)$this->config->get('cookie.ttl', 7200);
        $sql = 'INSERT INTO guard_sessions (session_hash, created_at, last_seen_at, expires_at) VALUES (?, NOW(6), NOW(6), DATE_ADD(NOW(6), INTERVAL ? SECOND)) '
             . 'ON DUPLICATE KEY UPDATE last_seen_at = NOW(6), expires_at = DATE_ADD(NOW(6), INTERVAL ? SECOND)';
        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute([$this->hash, $ttl, $ttl]);
    }
}
