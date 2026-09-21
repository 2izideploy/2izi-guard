<?php
declare(strict_types=1);

namespace TwoIzi\Guard\Security;

use TwoIzi\Guard\Support\Encoding;

final class Crypto
{
    public static function randomId(string $prefix, int $bytes = 18): string
    {
        return $prefix . Encoding::base64UrlEncode(random_bytes($bytes));
    }

    public static function randomToken(string $prefix = 'gt_', int $bytes = 32): string
    {
        return $prefix . Encoding::base64UrlEncode(random_bytes($bytes));
    }

    public static function sha256(string $value): string
    {
        return hash('sha256', $value, true);
    }

    public static function hmac(string $key, string $value): string
    {
        return hash_hmac('sha256', $value, $key, true);
    }

    public static function leadingZeroBits(string $binaryHash): int
    {
        $bits = 0;
        $len = strlen($binaryHash);
        for ($i = 0; $i < $len; $i++) {
            $byte = ord($binaryHash[$i]);
            if ($byte === 0) {
                $bits += 8;
                continue;
            }
            for ($b = 7; $b >= 0; $b--) {
                if (($byte & (1 << $b)) !== 0) {
                    return $bits;
                }
                $bits++;
            }
        }
        return $bits;
    }
}
