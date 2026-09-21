<?php
declare(strict_types=1);

use TwoIzi\Guard\Storage\Database;

/** @var TwoIzi\Guard\Core\Guard $guard */
$guard = require dirname(__DIR__) . '/bootstrap.php';
$config = $guard->config();
$db = new Database($config);
$pdo = $db->pdo();
$retentionDays = max(1, min(365, (int)$config->get('defaults.event_retention_days', 7)));

$queries = [
    'rate_limits' => 'DELETE FROM guard_rate_limits WHERE expires_at < NOW(6)',
    'tokens' => "DELETE FROM guard_tokens WHERE (status <> 'issued' OR expires_at < NOW(6)) AND COALESCE(consumed_at, revoked_at, expires_at) < DATE_SUB(NOW(6), INTERVAL 10 MINUTE)",
    // Never remove a challenge while an unexpired issued child token still exists.
    'challenges' => "DELETE c FROM guard_challenges c WHERE c.expires_at < DATE_SUB(NOW(6), INTERVAL 10 MINUTE) AND NOT EXISTS (SELECT 1 FROM guard_tokens t WHERE t.challenge_id = c.id AND t.status = 'issued' AND t.expires_at > NOW(6))",
    'sessions' => 'DELETE FROM guard_sessions WHERE expires_at < NOW(6)',
    'submissions' => 'DELETE FROM guard_submissions WHERE expires_at < NOW(6)',
    'events' => 'DELETE FROM guard_events WHERE event_time < DATE_SUB(NOW(6), INTERVAL ' . $retentionDays . ' DAY)',
];

foreach ($queries as $name => $sql) {
    $count = $pdo->exec($sql);
    echo $name . ': ' . (int)$count . PHP_EOL;
}
