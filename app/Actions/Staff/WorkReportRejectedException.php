<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use RuntimeException;

final class WorkReportRejectedException extends RuntimeException
{
    public function __construct(public readonly string $reasonCode)
    {
        parent::__construct("Work report rejected: {$reasonCode}");
    }

    public function getReason(): string
    {
        return $this->reasonCode;
    }
}
