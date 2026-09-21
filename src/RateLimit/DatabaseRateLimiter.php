<?php
declare(strict_types=1);

namespace TwoIzi\Guard\RateLimit;

use PDOException;
use TwoIzi\Guard\Storage\Database;
use TwoIzi\Guard\Security\Crypto;

final class DatabaseRateLimiter implements RateLimiterInterface
{
    public function __construct(private Database $db, private string $privacyKey) {}

    public function allow(string $key, int $capacity, int $periodSeconds): bool
    {
        return $this->consume($key, $capacity, $periodSeconds)->allowed();
    }

    public function consume(string $key, int $capacity, int $periodSeconds): RateLimitResult
    {
        if ($capacity < 1 || $periodSeconds < 1) {
            return new RateLimitResult(false, 0.0);
        }
        $hash = Crypto::hmac($this->privacyKey, $key);
        $pdo = $this->db->pdo();
        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('SELECT tokens, GREATEST(0, TIMESTAMPDIFF(MICROSECOND, updated_at, NOW(6))) / 1000000.0 AS elapsed_seconds FROM guard_rate_limits WHERE key_hash = ? FOR UPDATE');
                $stmt->execute([$hash]);
                $row = $stmt->fetch();
                if (!$row) {
                    $tokens = max(0.0, (float)$capacity - 1.0);
                    $ins = $pdo->prepare('INSERT INTO guard_rate_limits (key_hash, tokens, updated_at, expires_at) VALUES (?, ?, NOW(6), DATE_ADD(NOW(6), INTERVAL ? SECOND))');
                    $ins->execute([$hash, $tokens, max($periodSeconds * 2, 60)]);
                    $pdo->commit();
                    return new RateLimitResult(true, $tokens / $capacity);
                }
                $elapsed = max(0.0, (float)$row['elapsed_seconds']);
                $refillRate = $capacity / $periodSeconds;
                $tokens = min((float)$capacity, (float)$row['tokens'] + $elapsed * $refillRate);
                $allowed = $tokens >= 1.0;
                if ($allowed) {
                    $tokens -= 1.0;
                }
                $upd = $pdo->prepare('UPDATE guard_rate_limits SET tokens = ?, updated_at = NOW(6), expires_at = DATE_ADD(NOW(6), INTERVAL ? SECOND) WHERE key_hash = ?');
                $upd->execute([$tokens, max($periodSeconds * 2, 60), $hash]);
                $pdo->commit();
                return new RateLimitResult($allowed, max(0.0, min(1.0, $tokens / $capacity)));
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if ($attempt === 1) {
                    throw $e;
                }
            }
        }
        return new RateLimitResult(false, 0.0);
    }
}
