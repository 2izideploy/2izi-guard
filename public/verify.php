<?php
declare(strict_types=1);

require __DIR__ . '/_common.php';

$locale = is_string($data['locale'] ?? null) ? substr($data['locale'], 0, 32) : '';
try {
    $challengeId = is_string($data['challenge_id'] ?? null) ? substr($data['challenge_id'], 0, 64) : '';
    $nonce = is_string($data['solution']['nonce'] ?? null) ? substr($data['solution']['nonce'], 0, 128) : '';
    $honeypot = is_array($data['honeypot'] ?? null) ? $data['honeypot'] : [];
    $interaction = is_array($data['interaction'] ?? null) ? $data['interaction'] : [];
    $result = $guard->verifyChallenge($challengeId, $nonce, $honeypot, $interaction);
    echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (TwoIzi\Guard\Core\GuardHttpException $e) {
    http_response_code($e->status);
    $ui = $guard->ui($locale);
    $message = $e->getMessage() === 'rate_limited' ? ($ui['rate_limited'] ?? '') : ($ui['failed'] ?? '');
    echo json_encode(['success' => false, 'code' => $e->getMessage(), 'message' => $message], JSON_UNESCAPED_UNICODE);
} catch (Throwable) {
    http_response_code(500);
    echo json_encode(['success' => false, 'code' => 'guard_internal_error']);
}
