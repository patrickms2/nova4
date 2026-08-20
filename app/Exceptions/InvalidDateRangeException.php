<?php

declare(strict_types=1);

namespace App\Exceptions;

use InvalidArgumentException;

/**
 * Rzucane gdy start_date > end_date przy zapisie Rental/AvailabilitySlot.
 */
class InvalidDateRangeException extends InvalidArgumentException
{
    public static function startAfterEnd(string $startDate, string $endDate): self
    {
        return new self(sprintf(
            'Nieprawidłowy zakres dat: start_date (%s) musi być <= end_date (%s).',
            $startDate,
            $endDate
        ));
    }
}
