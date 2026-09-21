<?php
declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'TwoIzi\\Guard\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = substr($class, strlen($prefix));
    $path = dirname(__DIR__) . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) require_once $path;
});

use TwoIzi\Guard\Config\Config;
use TwoIzi\Guard\Security\TrustedProxyResolver;
use TwoIzi\Guard\Support\Encoding;

function check(bool $condition, string $message): void {
    if (!$condition) { fwrite(STDERR, "FAIL: {$message}\n"); exit(1); }
    echo "OK: {$message}\n";
}
$key = 'base64url:' . Encoding::base64UrlEncode(str_repeat('K', 32));
$config = new Config([
    'origin' => 'https://example.com',
    'database' => [],
    'keys' => ['hmac' => $key, 'privacy' => $key],
    'actions' => ['contact' => []],
    'trusted_proxies' => ['10.0.0.0/8'],
]);
$_SERVER['REMOTE_ADDR'] = '10.0.0.2';
$_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4, 198.51.100.20';
$resolver = new TrustedProxyResolver($config);
check($resolver->clientIp() === '198.51.100.20', 'XFF is walked right-to-left and spoofed left prefix is ignored');
$_SERVER['REMOTE_ADDR'] = '203.0.113.10';
$_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4';
check($resolver->clientIp() === '203.0.113.10', 'forwarded headers ignored from untrusted direct peer');

echo "Proxy tests passed.\n";
