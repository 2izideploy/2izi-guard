<?php
declare(strict_types=1);

/** @var TwoIzi\Guard\Core\Guard $guard */
$guard = require dirname(__DIR__) . '/safe-bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $result = $guard->verifyAndConsume($guard->tokenFromRequest(), 'contact', $guard->submissionIdFromRequest());
    if (!$result->allowed()) {
        http_response_code(403);
        exit('Guard verification failed');
    }
    echo 'Protected action allowed';
    exit;
}
?><!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>2IZI Guard example</title></head>
<body>
<form method="post" data-guard-action="contact">
    <input name="message" required>
    <button type="submit">Send</button>
</form>
<script src="../public/assets/guard.js" data-guard-base="../public" data-guard-fail-open="true" data-guard-isolation="shadow" defer></script>
</body>
</html>
