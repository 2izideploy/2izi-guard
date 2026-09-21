<?php
declare(strict_types=1);

namespace TwoIzi\Guard\RateLimit;

final class RateLimitResult
{
    public function __construct(
        private bool $allowed,
        private float $remainingRatio,
    ) {}

    public function allowed(): bool { return $this->allowed; }
    public function remainingRatio(): float { return $this->remainingRatio; }
    public function pressure(): float { return max(0.0, min(1.0, 1.0 - $this->remainingRatio)); }
}
