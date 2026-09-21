<?php
declare(strict_types=1);

namespace TwoIzi\Guard\Security;

use TwoIzi\Guard\Config\Config;

final class TrustedProxyResolver
{
    public function __construct(private Config $config) {}

    public function clientIp(): string
    {
        $remote = $this->normalize((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')) ?? '0.0.0.0';
        if (!$this->isTrusted($remote)) {
            return $remote;
        }

        $forwarded = (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        if ($forwarded !== '') {
            $chain = [];
            foreach (explode(',', $forwarded) as $part) {
                $candidate = $this->normalize(trim($part));
                if ($candidate !== null) {
                    $chain[] = $candidate;
                }
            }
            // Walk from the proxy nearest us toward the original client. Never trust
            // a spoofable value prepended to the left of an already-untrusted hop.
            for ($i = count($chain) - 1; $i >= 0; $i--) {
                if (!$this->isTrusted($chain[$i])) {
                    return $chain[$i];
                }
            }
        }

        $real = $this->normalize((string)($_SERVER['HTTP_X_REAL_IP'] ?? ''));
        return $real ?? $remote;
    }

    public function networkKey(): string
    {
        $ip = $this->clientIp();
        $packed = @inet_pton($ip);
        if ($packed === false || strlen($packed) === 4) {
            return $ip;
        }
        // Use /64 as a coarse IPv6 network signal; do not treat it as identity.
        $prefix = substr($packed, 0, 8) . str_repeat("\0", 8);
        return (string)inet_ntop($prefix) . '/64';
    }

    private function normalize(string $ip): ?string
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }
        $packed = @inet_pton($ip);
        return $packed === false ? null : (string)inet_ntop($packed);
    }

    private function isTrusted(string $ip): bool
    {
        foreach ((array)$this->config->get('trusted_proxies', []) as $cidr) {
            if ($this->inCidr($ip, (string)$cidr)) {
                return true;
            }
        }
        return false;
    }

    private function inCidr(string $ip, string $cidr): bool
    {
        if (!str_contains($cidr, '/')) {
            $normalized = $this->normalize($cidr);
            return $normalized !== null && hash_equals($normalized, $ip);
        }
        [$network, $prefix] = explode('/', $cidr, 2);
        $ipBin = @inet_pton($ip);
        $netBin = @inet_pton($network);
        if ($ipBin === false || $netBin === false || strlen($ipBin) !== strlen($netBin)) {
            return false;
        }
        $prefix = (int)$prefix;
        $maxBits = strlen($ipBin) * 8;
        if ($prefix < 0 || $prefix > $maxBits) {
            return false;
        }
        $full = intdiv($prefix, 8);
        $rem = $prefix % 8;
        if ($full > 0 && substr($ipBin, 0, $full) !== substr($netBin, 0, $full)) {
            return false;
        }
        if ($rem === 0) {
            return true;
        }
        $mask = (0xFF << (8 - $rem)) & 0xFF;
        return (ord($ipBin[$full]) & $mask) === (ord($netBin[$full]) & $mask);
    }
}
