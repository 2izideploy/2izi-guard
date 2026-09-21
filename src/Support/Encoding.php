<?php
declare(strict_types=1);

namespace TwoIzi\Guard\Support;

final class Encoding
{
    public static function base64UrlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $value): string
    {
        $padding = (4 - strlen($value) % 4) % 4;
        $decoded = base64_decode(strtr($value . str_repeat('=', $padding), '-_', '+/'), true);
        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid base64url value.');
        }
        return $decoded;
    }
}
