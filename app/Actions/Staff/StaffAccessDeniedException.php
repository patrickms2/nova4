<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use RuntimeException;

final class StaffAccessDeniedException extends RuntimeException
{
    public function __construct(public readonly string $reasonCode)
    {
        parent::__construct("Staff access denied: {$reasonCode}");
    }

    public function getReason(): string
    {
        return $this->reasonCode;
    }
}
