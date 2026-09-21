<?php
declare(strict_types=1);

namespace TwoIzi\Guard\Core;

final class GuardHttpException extends \RuntimeException
{
    public function __construct(public readonly int $status, string $publicCode)
    {
        parent::__construct($publicCode);
    }
}
