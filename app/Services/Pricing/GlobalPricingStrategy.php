<?php

declare(strict_types=1);

namespace App\Services\Pricing;

use Illuminate\Database\Eloquent\Model;
use App\Contracts\PricingStrategyInterface;
use App\Models\RentalType;

/**
 * Globalna strategia cenowa — port pierwotnego zachowania pakietu (z blizne-art-cms).
 *
 * Cena zależy WYŁĄCZNIE od RentalType (nie od konkretnego rentable):
 *
 *     totalAmount = rentalType.price * qty
 *
 * Pasuje do scenariuszy gdzie cena jest atrybutem typu wynajmu/biletu/pakietu:
 *  - Bilety na wycieczki ("Wycieczka standardowa" = 50 zł, "VIP" = 100 zł)
 *  - Pakiety usługowe ("Basic" = 100, "Pro" = 200)
 *  - Wynajem ekskluzywny gdzie cena jest niezależna od konkretnego egzemplarza
 *    (np. wszystkie pokoje hotelowe tej samej kategorii — ta sama cena)
 *
 * Backward compat: domyślna strategia, używana gdy config('rental.pricing_strategy')
 * = 'global' lub gdy klucz jest pominięty.
 */
class GlobalPricingStrategy implements PricingStrategyInterface
{
    public function calculate(
        Model $rentable,
        ?RentalType $rentalType,
        int $qty,
        string $startDate,
        string $endDate,
        array $payload = []
    ): int {

        $unitPrice = (int) ($rentalType?->price ?? 0);

        return $unitPrice * max(1, $qty);
    }
}
