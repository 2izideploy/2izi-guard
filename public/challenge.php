<?php
declare(strict_types=1);

require __DIR__ . '/_common.php';

$locale = is_string($data['locale'] ?? null) ? substr($data['locale'], 0, 32) : '';
try {
    $action = is_string($data['action'] ?? null) ? substr($data['action'], 0, 64) : '';
    $signals = is_array($data['signals'] ?? null) ? $data['signals'] : [];
    echo json_encode($guard->challenge($action, $signals, $locale), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (TwoIzi\Guard\Core\GuardHttpException $e) {
    http_response_code($e->status);
    $ui = $guard->ui($locale);
    $message = $e->getMessage() === 'rate_limited' ? ($ui['rate_limited'] ?? '') : ($ui['failed'] ?? '');
    echo json_encode(['success' => false, 'code' => $e->getMessage(), 'message' => $message], JSON_UNESCAPED_UNICODE);
} catch (Throwable) {
    http_response_code(500);
    echo json_encode(['success' => false, 'code' => 'guard_internal_error']);
}
