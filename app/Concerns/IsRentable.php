<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\AvailabilitySlot;
use App\Models\Rental;

/**
 * Trait do oznaczenia modelu jako Rentable.
 *
 * Uzycie:
 *   class Motorcycle extends Model implements \App\Contracts\Rentable
 *   {
 *       use \App\Concerns\IsRentable;
 *   }
 *
 * Domyslne implementacje getRentableName/Slug zakladaja, ze model ma
 * pola "name" i "slug". Aplikacja moze nadpisac metody jesli ma inne
 * konwencje.
 */
trait IsRentable
{
    public function rentals(): MorphMany
    {
        return $this->morphMany(Rental::class, 'rentable');
    }

    public function availabilitySlots(): MorphMany
    {
        return $this->morphMany(AvailabilitySlot::class, 'rentable');
    }

    public function getRentableId(): int|string
    {
        return $this->getKey();
    }

    public function getRentableName(): string
    {
        return (string) ($this->name ?? $this->getKey());
    }

    public function getRentableSlug(): string
    {
        return (string) ($this->slug ?? $this->getKey());
    }

    /**
     * Czy zasob jest dostepny w podanym zakresie dat.
     *
     * Sprawdza:
     *  - aktywne Rentals (status != cancelled/expired) overlap
     *  - blokady (AvailabilitySlot is_blocked=true) overlap
     *
     * UWAGA: ta metoda nie ma DB lock — do faktycznej rezerwacji uzywaj
     * RentalService::book() (KML-0047) z lockForUpdate w transakcji.
     *
     * @param  string|null  $excludeRentalId  pomin konkretny Rental (przy edycji)
     */
    public function isAvailable(string $startDate, string $endDate, ?string $excludeRentalId = null): bool
    {
        $rentalsQuery = $this->rentals()
            ->active()
            ->overlapping($startDate, $endDate);

        if ($excludeRentalId !== null) {
            $rentalsQuery->where('id', '!=', $excludeRentalId);
        }

        if ($rentalsQuery->exists()) {
            return false;
        }

        $slotsQuery = $this->availabilitySlots()
            ->blocked()
            ->overlapping($startDate, $endDate);

        return ! $slotsQuery->exists();
    }
}
