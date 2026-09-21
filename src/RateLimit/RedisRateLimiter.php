<?php
declare(strict_types=1);

namespace TwoIzi\Guard\RateLimit;

use TwoIzi\Guard\Config\Config;
use TwoIzi\Guard\Security\Crypto;

/** Optional atomic token-bucket backend using ext-redis. */
final class RedisRateLimiter implements RateLimiterInterface
{
    private \Redis $redis;
    private string $prefix;

    public function __construct(Config $config, private string $privacyKey)
    {
        if (!extension_loaded('redis') || !class_exists(\Redis::class)) {
            throw new \RuntimeException('Redis rate limiter selected but ext-redis is not installed.');
        }
        $r = (array)$config->get('rate_limit.redis', []);
        $this->prefix = (string)($r['prefix'] ?? 'izi_guard:rl:');
        $this->redis = new \Redis();
        $ok = $this->redis->connect(
            (string)($r['host'] ?? '127.0.0.1'),
            (int)($r['port'] ?? 6379),
            (float)($r['timeout'] ?? 1.0)
        );
        if (!$ok) {
            throw new \RuntimeException('Unable to connect to Redis for 2IZI Guard.');
        }
        if (isset($r['password']) && (string)$r['password'] !== '') {
            if (!$this->redis->auth((string)$r['password'])) {
                throw new \RuntimeException('Redis authentication failed for 2IZI Guard.');
            }
        }
        if (isset($r['database'])) {
            $this->redis->select((int)$r['database']);
        }
    }

    public function allow(string $key, int $capacity, int $periodSeconds): bool
    {
        return $this->consume($key, $capacity, $periodSeconds)->allowed();
    }

    public function consume(string $key, int $capacity, int $periodSeconds): RateLimitResult
    {
        if ($capacity < 1 || $periodSeconds < 1) {
            return new RateLimitResult(false, 0.0);
        }
        $hash = bin2hex(Crypto::hmac($this->privacyKey, $key));
        $redisKey = $this->prefix . $hash;
        $nowMs = (int)floor(microtime(true) * 1000);
        $ttlMs = max($periodSeconds * 2000, 60000);
        $script = <<<'LUA'
local key = KEYS[1]
local capacity = tonumber(ARGV[1])
local period_ms = tonumber(ARGV[2])
local now_ms = tonumber(ARGV[3])
local ttl_ms = tonumber(ARGV[4])
local refill_per_ms = capacity / period_ms
local data = redis.call('HMGET', key, 'tokens', 'updated')
local tokens = tonumber(data[1])
local updated = tonumber(data[2])
if tokens == nil or updated == nil then
  tokens = capacity
  updated = now_ms
end
local elapsed = math.max(0, now_ms - updated)
tokens = math.min(capacity, tokens + elapsed * refill_per_ms)
local allowed = 0
if tokens >= 1 then
  tokens = tokens - 1
  allowed = 1
end
redis.call('HSET', key, 'tokens', tostring(tokens), 'updated', tostring(now_ms))
redis.call('PEXPIRE', key, ttl_ms)
return {allowed, tostring(tokens / capacity)}
LUA;
        $result = $this->redis->eval($script, [$redisKey, $capacity, $periodSeconds * 1000, $nowMs, $ttlMs], 1);
        if (!is_array($result) || count($result) < 2) {
            throw new \RuntimeException('Invalid Redis rate limiter response.');
        }
        return new RateLimitResult((int)$result[0] === 1, max(0.0, min(1.0, (float)$result[1])));
    }
}
