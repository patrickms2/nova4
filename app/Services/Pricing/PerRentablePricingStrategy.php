<?php

declare(strict_types=1);

namespace App\Services\Pricing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use App\Contracts\PricingStrategyInterface;
use App\Models\RentalType;

/**
 * Strategia "cena z modelu Rentable" — dla scenariuszy gdzie każdy zasób ma
 * własny cennik (motocykle, pokoje hotelowe z różnymi stawkami, sprzęt z amortyzacją).
 *
 * Geneza: dodana dla projektu 2wheels-rental.pl (KML / STR-0008), który ma cennik
 * per-motocykl w polach `price_per_day`, `price_per_week`, `price_per_month` na
 * tabeli `two_wheels_motorcycles`. Wcześniejszy model (GlobalPricingStrategy)
 * zakładał globalny cennik typów co nie pasowało do tego use-case.
 *
 * Algorytm:
 *
 *     unitPrice = rentable.{rental_type.meta.rentable_field}
 *     units     = ceil(diff(startDate, endDate) / unitDuration(rental_type.meta.unit))
 *     totalAmount = unitPrice * units * qty
 *
 * RentalType.meta musi zawierać:
 *  - `unit`            — `day` | `week` | `month` (jednostka czasu do mnożenia)
 *  - `rentable_field`  — nazwa kolumny na modelu Rentable z ceną jednostkową
 *
 * Domyślne wartości (gdy meta brak):
 *  - unit = 'day'
 *  - rentable_field = 'price_per_day'
 *
 * Przykład konfiguracji RentalType dla 2wheels:
 *
 *     RentalType::create([
 *         'slug' => 'dzien',
 *         'label' => 'Wynajem dobowy',
 *         'price' => 0, // unused w per_rentable mode
 *         'meta' => ['unit' => 'day', 'rentable_field' => 'price_per_day'],
 *     ]);
 */
class PerRentablePricingStrategy implements PricingStrategyInterface
{
    public function calculate(
        Model $rentable,
        ?RentalType $rentalType,
        int $qty,
        string $startDate,
        string $endDate,
        array $payload = []
    ): int {
        $meta = (array) ($rentalType?->meta ?? []);
        $unit = $meta['unit'] ?? config('rental.pricing_default_unit', 'day');
        $field = $meta['rentable_field'] ?? config('rental.pricing_default_rentable_field', 'price_per_day');
        $unitPrice = (float) ($rentable->{$field} ?? 0);
        if ($unitPrice <= 0) {
            return 0;
        }

        $units = $this->countUnits($startDate, $endDate, (string) $unit);
        $qty = max(1, $qty);

        return (int) round($unitPrice * $units * $qty);
    }

    /**
     * Liczba jednostek czasu między dwoma datami (zaokrąglona w górę).
     *
     * Przykłady:
     *  - day, 2026-01-01..2026-01-03  → 2 dni
     *  - week, 2026-01-01..2026-01-15 → 2 tygodnie
     *  - month, 2026-01-01..2026-03-15 → 3 miesiące
     */
    private function countUnits(string $start, string $end, string $unit): int
    {
        $startCarbon = Carbon::parse($start);
        $endCarbon = Carbon::parse($end);

        $diffHours = max(0.0, (float) $startCarbon->diffInHours($endCarbon, true));
        if ($diffHours <= 0.0) {
            return 1;
        }

        $diffDays = $diffHours / 24.0;

        return match ($unit) {
            'day' => max(1, (int) ceil($diffDays)),
            'week' => max(1, (int) ceil($diffDays / 7.0)),
            'month' => max(1, (int) ceil($diffDays / 30.0)),
            'hour' => max(1, (int) ceil($diffHours)),
            default => max(1, (int) ceil($diffDays)),
        };
    }
}
