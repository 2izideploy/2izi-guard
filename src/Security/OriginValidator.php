<?php
declare(strict_types=1);

namespace TwoIzi\Guard\Security;

use TwoIzi\Guard\Config\Config;

final class OriginValidator
{
    public function __construct(private Config $config) {}

    public function expectedOrigin(): string
    {
        return rtrim(strtolower((string)$this->config->get('origin')), '/');
    }

    public function validateRequest(): bool
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin === '') {
            if ((bool)$this->config->get('require_origin_header', true)) {
                return false;
            }
            return true;
        }
        return hash_equals($this->expectedOrigin(), rtrim(strtolower($origin), '/'));
    }

    public function hash(string $origin, string $privacyKey): string
    {
        return Crypto::hmac($privacyKey, rtrim(strtolower($origin), '/'));
    }
}
