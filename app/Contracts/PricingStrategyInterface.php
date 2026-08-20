<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;
use App\Models\RentalType;

/**
 * Strategia obliczania ceny rezerwacji.
 *
 * Pakiet wspiera dwa modele biznesowe (patrz README sekcja "Pricing models"):
 *
 *  - GlobalPricingStrategy   — cena globalna z RentalType.price * qty
 *                              (przykład: blizne-art-cms TicketType, "z kaskiem"=200, "bez"=180)
 *
 *  - PerRentablePricingStrategy — cena z pola modelu Rentable, np.
 *                                  motorcycle.price_per_day * dni
 *                                  (przykład: 2wheels-rental.pl)
 *
 * Wybór przez config('rental.pricing_strategy'). Aplikacja może też zarejestrować
 * własną strategię przez container binding:
 *
 *   $this->app->singleton(PricingStrategyInterface::class, MyPricingStrategy::class);
 */
interface PricingStrategyInterface
{
    /**
     * Oblicza całkowitą kwotę rezerwacji.
     *
     * @param  Model  $rentable  zasób (Motorcycle, Sala, Sprzet, ...)
     * @param  RentalType|null  $rentalType  typ wynajmu (null gdy niezdefiniowany)
     * @param  int  $qty  ilość (np. liczba sztuk, liczba osób)
     * @param  string  $startDate  data rozpoczęcia (Y-m-d lub Y-m-d H:i:s)
     * @param  string  $endDate  data zakończenia
     * @param  array<string,mixed>  $payload  dodatkowe dane z API request (opcjonalne)
     * @return int kwota w jednostkach głównych waluty (np. PLN, nie groszach)
     */
    public function calculate(
        Model $rentable,
        ?RentalType $rentalType,
        int $qty,
        string $startDate,
        string $endDate,
        array $payload = []
    ): int;
}
