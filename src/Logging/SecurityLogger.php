<?php
declare(strict_types=1);

namespace TwoIzi\Guard\Logging;

use TwoIzi\Guard\Security\Crypto;
use TwoIzi\Guard\Storage\Database;

final class SecurityLogger
{
    public function __construct(private Database $db, private string $privacyKey) {}

    public function log(string $action, string $sessionHash, string $network, int $risk, string $decision, array $reasons = [], int $latencyMs = 0, ?string $predictedDecision = null, ?string $mode = null): string
    {
        $requestId = 'GRD-' . strtoupper(substr(bin2hex(random_bytes(8)), 0, 16));
        // Rotate the event correlation key daily so raw network identity cannot be correlated indefinitely.
        $dailyKey = Crypto::hmac($this->privacyKey, 'event-log|' . gmdate('Y-m-d'));
        $networkHash = Crypto::hmac($dailyKey, $network);
        $stmt = $this->db->pdo()->prepare(
            'INSERT INTO guard_events (event_time, request_id, action, session_hash_short, network_hash, risk_score, decision, reason_codes, latency_ms, predicted_decision, mode) '
          . 'VALUES (NOW(6), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $requestId,
            $action,
            substr(bin2hex($sessionHash), 0, 16),
            $networkHash,
            max(0, min(100, $risk)),
            $decision,
            json_encode(array_values($reasons), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            max(0, $latencyMs),
            $predictedDecision,
            $mode,
        ]);
        return $requestId;
    }
}
