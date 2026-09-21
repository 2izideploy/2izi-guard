<?php
declare(strict_types=1);

namespace TwoIzi\Guard\Token;

final class TokenConsumeResult
{
    public function __construct(private bool $valid, private string $reason) {}
    public function valid(): bool { return $this->valid; }
    public function reason(): string { return $this->reason; }
}
