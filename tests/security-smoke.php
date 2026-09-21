<?php
declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'TwoIzi\\Guard\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = substr($class, strlen($prefix));
    $path = dirname(__DIR__) . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) require_once $path;
});

use TwoIzi\Guard\Security\Crypto;
use TwoIzi\Guard\Support\Encoding;

function check(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    echo "OK: {$message}\n";
}

$raw = random_bytes(32);
$enc = Encoding::base64UrlEncode($raw);
check(hash_equals($raw, Encoding::base64UrlDecode($enc)), 'base64url round-trip');

$token = Crypto::randomToken();
check(str_starts_with($token, 'gt_'), 'token prefix');
check(strlen(Encoding::base64UrlDecode(substr($token, 3))) === 32, 'token entropy is 256 bits');

check(Crypto::leadingZeroBits("\x00\x00\x10" . str_repeat("\x00", 29)) === 19, 'leading zero bit counter');

$challenge = 'ch_test';
$salt = 'salt_test';
$difficulty = 12;
$nonce = 0;
do {
    $hash = hash('sha256', "v1|{$challenge}|{$salt}|{$nonce}", true);
    if (Crypto::leadingZeroBits($hash) >= $difficulty) break;
    $nonce++;
} while ($nonce < 2_000_000);
check($nonce < 2_000_000, 'PoW vector solvable');
check(Crypto::leadingZeroBits(hash('sha256', "v1|{$challenge}|{$salt}|{$nonce}", true)) >= $difficulty, 'PoW verification matches protocol');

echo "Smoke tests passed. nonce={$nonce}\n";
