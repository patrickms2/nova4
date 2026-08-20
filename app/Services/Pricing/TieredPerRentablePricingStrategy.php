<?php

declare(strict_types=1);

namespace App\Services\Pricing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use App\Contracts\PricingStrategyInterface;
use App\Models\RentalType;

/**
 * Strategia tiered per-rentable — geneza: 2wheels-rental.pl (KML-0051).
 *
 * Roznica wzgledem PerRentablePricingStrategy: zamiast jednej stawki (dzien/tydzien/miesiac)
 * wybranej przez RentalType, ta strategia uzywa WSZYSTKICH trzech stawek z modelu Rentable
 * i sklada cene "greedy" wg dekompozycji: miesiace → tygodnie → dni. Pozwala to klientowi
 * dostac automatyczna znizke za pelny tydzien / pelny miesiac bez koniecznosci wyboru
 * konkretnego typu wynajmu.
 *
 * Algorytm:
 *
 *     N      = liczba dni rezerwacji (tak jak PerRentablePricingStrategy: diff exclusive end)
 *     months = floor(N / 30) jesli monthly_rate > 0
 *     rem    = N - months * 30
 *     weeks  = floor(rem / 7) jesli weekly_rate > 0
 *     days   = rem - weeks * 7
 *     total  = (months * monthly + weeks * weekly + days * daily) * qty
 *
 * Przyklad (yamaha-tracer-9-gt: dzien=400, tydzien=2600, miesiac=10000):
 *   - 6 dni  → 6 * 400 = 2400
 *   - 7 dni  → 2600
 *   - 9 dni  → 2600 + 2 * 400 = 3400
 *   - 30 dni → 10000
 *   - 37 dni → 10000 + 2600 = 12600
 *
 * Konfiguracja przez RentalType.meta (opcjonalnie):
 *  - `daily_field`   — nazwa kolumny ze stawka dzienna  (default: price_per_day)
 *  - `weekly_field`  — nazwa kolumny ze stawka tygodniowa (default: price_per_week)
 *  - `monthly_field` — nazwa kolumny ze stawka miesieczna (default: price_per_month)
 *
 * Fallback: jesli stawka tygodniowa = 0 → uzywaj dziennej dla calej reszty po miesiacach.
 *           Jesli stawka miesieczna = 0 → liczymy tylko tygodnie + dni.
 */
class TieredPerRentablePricingStrategy implements PricingStrategyInterface
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
        $dailyField = $meta['daily_field'] ?? 'price_per_day';
        $weeklyField = $meta['weekly_field'] ?? 'price_per_week';
        $monthlyField = $meta['monthly_field'] ?? 'price_per_month';

        $daily = (float) ($rentable->{$dailyField} ?? 0);
        $weekly = (float) ($rentable->{$weeklyField} ?? 0);
        $monthly = (float) ($rentable->{$monthlyField} ?? 0);

        if ($daily <= 0 && $weekly <= 0 && $monthly <= 0) {
            return 0;
        }

        $days = $this->countDays($startDate, $endDate);
        $qty = max(1, $qty);

        $months = 0;
        if ($monthly > 0) {
            $months = intdiv($days, 30);
            $days -= $months * 30;
        }

        $weeks = 0;
        if ($weekly > 0) {
            $weeks = intdiv($days, 7);
            $days -= $weeks * 7;
        }

        $total = ($months * $monthly) + ($weeks * $weekly) + ($days * $daily);

        return (int) round($total * $qty);
    }

    /**
     * Liczba dni miedzy dwoma datami (zaokrąglona w górę, minimum 1).
     * Zachowuje semantyke PerRentablePricingStrategy: diff exclusive end.
     */
    private function countDays(string $start, string $end): int
    {
        $startCarbon = Carbon::parse($start);
        $endCarbon = Carbon::parse($end);

        $diffHours = max(0.0, (float) $startCarbon->diffInHours($endCarbon, true));
        if ($diffHours <= 0.0) {
            return 1;
        }

        return max(1, (int) ceil($diffHours / 24.0));
    }
}
