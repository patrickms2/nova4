<?php

declare(strict_types=1);

namespace App\Domain\Staff;

final readonly class AccessEvaluationResult
{
    private function __construct(
        public bool $authorized,
        public ?string $reason = null,
    ) {}

    public static function authorized(): self
    {
        return new self(true);
    }

    public static function denied(string $reason): self
    {
        return new self(false, $reason);
    }
}
