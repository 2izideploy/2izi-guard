<?php
declare(strict_types=1);

namespace TwoIzi\Guard\Core;

final class GuardResult
{
    public function __construct(
        private bool $allowed,
        private string $code,
        private ?string $predictedDecision = null,
    ) {}

    public function allowed(): bool { return $this->allowed; }
    public function code(): string { return $this->code; }
    public function predictedDecision(): ?string { return $this->predictedDecision; }
    public function replayed(): bool { return $this->code === 'idempotent_replay'; }
}
