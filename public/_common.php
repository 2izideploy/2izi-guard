<?php
declare(strict_types=1);

use TwoIzi\Guard\Core\GuardHttpException;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['success' => false, 'code' => 'method_not_allowed']);
    exit;
}

$contentType = strtolower(trim(explode(';', (string)($_SERVER['CONTENT_TYPE'] ?? ''))[0]));
if ($contentType !== 'application/json') {
    http_response_code(415);
    echo json_encode(['success' => false, 'code' => 'unsupported_media_type']);
    exit;
}

$length = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($length > 16384) {
    http_response_code(413);
    echo json_encode(['success' => false, 'code' => 'request_too_large']);
    exit;
}

try {
    $guard = require dirname(__DIR__) . '/bootstrap.php';
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true, 16, JSON_THROW_ON_ERROR);
    if (!is_array($data)) {
        throw new JsonException('Expected JSON object.');
    }
} catch (GuardHttpException $e) {
    http_response_code($e->status);
    echo json_encode(['success' => false, 'code' => $e->getMessage()]);
    exit;
} catch (JsonException) {
    http_response_code(400);
    echo json_encode(['success' => false, 'code' => 'invalid_json']);
    exit;
} catch (Throwable) {
    http_response_code(500);
    echo json_encode(['success' => false, 'code' => 'guard_internal_error']);
    exit;
}
