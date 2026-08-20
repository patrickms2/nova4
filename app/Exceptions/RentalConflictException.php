<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Rzucane gdy zasob nie jest dostepny w zadanym zakresie dat
 * (overlapping aktywny Rental albo zablokowany AvailabilitySlot).
 *
 * Mapowane na HTTP 409 Conflict w warstwie API.
 */
class RentalConflictException extends RuntimeException
{
    public static function rentalOverlap(string $rentableType, int|string $rentableId, string $start, string $end): self
    {
        return new self(sprintf(
            'Zasób %s#%s jest już zarezerwowany w zakresie %s - %s.',
            $rentableType,
            (string) $rentableId,
            $start,
            $end
        ));
    }

    public static function blocked(string $rentableType, int|string $rentableId, string $start, string $end): self
    {
        return new self(sprintf(
            'Zasób %s#%s jest niedostępny (blokada) w zakresie %s - %s.',
            $rentableType,
            (string) $rentableId,
            $start,
            $end
        ));
    }
}
