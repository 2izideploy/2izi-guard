<?php
declare(strict_types=1);

namespace TwoIzi\Guard\Risk;

final class RiskAssessment
{
    public function __construct(
        private int $score,
        private array $reasons,
    ) {}

    public function score(): int { return $this->score; }
    public function reasons(): array { return $this->reasons; }
}
