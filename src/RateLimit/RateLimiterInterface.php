<?php
declare(strict_types=1);

namespace TwoIzi\Guard\RateLimit;

interface RateLimiterInterface
{
    public function consume(string $key, int $capacity, int $periodSeconds): RateLimitResult;
    public function allow(string $key, int $capacity, int $periodSeconds): bool;
}
