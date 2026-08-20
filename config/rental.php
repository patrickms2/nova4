<?php

declare(strict_types=1);
use App\Services\Pricing\GlobalPricingStrategy;
use App\Services\Pricing\PerRentablePricingStrategy;
use App\Services\Pricing\TieredPerRentablePricingStrategy;

return [

    /*
    |--------------------------------------------------------------------------
    | Filament panel ID
    |--------------------------------------------------------------------------
    |
    | ID panelu Filament, do ktorego RentalPlugin podpina swoje zasoby
    | (RentalResource, AvailabilitySlotResource, RentalCalendar). Domyslnie
    | "admin" — zgodnie ze standardowym konwencja Laravel/Filament.
    |
    */
    'panel' => env('RENTAL_PANEL', 'admin'),

    /*
    |--------------------------------------------------------------------------
    | API routes
    |--------------------------------------------------------------------------
    |
    | Aplikacja moze wylaczyc wbudowane trasy REST i zarejestrowac wlasne.
    | Trasy implementowane sa w E-grupie (KML-0056..0058).
    |
    */
    'enable_api_routes' => env('RENTAL_ENABLE_API_ROUTES', true),
    'route_prefix' => env('RENTAL_ROUTE_PREFIX', 'api'),
    'route_middleware' => ['api'],

    /*
    |--------------------------------------------------------------------------
    | Tabele
    |--------------------------------------------------------------------------
    |
    | Aplikacja moze nadpisac nazwy tabel. Migracje publikowalne uzywaja
    | tych wartosci.
    |
    */
    'tables' => [
        'rentals' => 'rentals',
        'availability_slots' => 'availability_slots',
        'rental_types' => 'rental_types',
    ],

    /*
    |--------------------------------------------------------------------------
    | Model wynajmowanego zasobu (Rentable)
    |--------------------------------------------------------------------------
    |
    | FQCN modelu pełniacego role Rentable (np. Motocykl, Sala, Sprzet).
    | Ustawiane per-aplikacja, np. App\Models\Motorcycle::class.
    |
    */
    'rentable_model' => App\Models\Admin\Villa::class,

    /*n
    |--------------------------------------------------------------------------
    | Wiele modeli rentable (D3 — AvailabilitySlot, multi-resource panel)
    |--------------------------------------------------------------------------
    |
    | Dla aplikacji wynajmujacych kilka typow zasobow (np. blizne: sale + sprzet,
    | hotel: pokoje + auta + sprzet). Format:
    |
    |   ['App\Models\Motorcycle' => 'Motocykle', 'App\Models\Helmet' => 'Kaski']
    |
    | LUB list (klasa => class_basename jako label):
    |
    |   ['App\Models\Motorcycle', 'App\Models\Helmet']
    |
    | Jesli nie ustawione, AvailabilitySlotResource uzywa rentable_model jako
    | jedyny typ.
    |
    */
    'rentable_models' => ['App\Models\Rental', 'App\Models\Admin\Villa' => 'Villa'],

    /*
    |--------------------------------------------------------------------------
    | Provider platnosci
    |--------------------------------------------------------------------------
    |
    | FQCN klasy implementujacej App\Contracts\PaymentProvider.
    | Domyslnie null — modul moze dzialac bez platnosci (tylko email).
    |
    | Aby uzywac Przelewy24:
    |   'payment_provider' => \App\Services\Przelewy24Service::class,
    |
    | Aplikacja moze tez zaimplementowac wlasny provider (np. Stripe, PayU).
    |
    */
    'payment_provider' => env('RENTAL_PAYMENT_PROVIDER'),

    /*
    |--------------------------------------------------------------------------
    | Przelewy24 — konfiguracja (opcjonalna)
    |--------------------------------------------------------------------------
    |
    | Ustawienia dla Przelewy24Service gdy uzywane z .env (zamiast DB).
    | Preferowane jest czytanie credentials z bazy danych przez
    | Przelewy24Service::fromSettings($dbSettings).
    |
    | Te wartosci sa uzywane tylko gdy aplikacja binduje serwis z config:
    |   $this->app->singleton(PaymentProvider::class, fn () =>
    |       new Przelewy24Service(config('rental.przelewy24'))
    |   );
    |
    */
    'przelewy24' => [
        'merchant_id' => env('P24_MERCHANT_ID'),
        'pos_id' => env('P24_POS_ID'),
        'crc' => env('P24_CRC'),
        'api_key' => env('P24_API_KEY'),
        'sandbox' => env('P24_SANDBOX', true),
        'webhook_path' => env('P24_WEBHOOK_PATH', '/api/payment/webhook'),
        'return_path' => env('P24_RETURN_PATH', '/rezerwacja/status'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Waluta i polityka rezerwacji
    |--------------------------------------------------------------------------
    */
    'currency' => env('RENTAL_CURRENCY', 'EUR'),
    'expire_unpaid_after_minutes' => env('RENTAL_EXPIRE_AFTER', 30),

    /*
    |--------------------------------------------------------------------------
    | Strategia obliczania ceny (Pricing model)
    |--------------------------------------------------------------------------
    |
    | Pakiet wspiera dwa modele biznesowe (patrz README "Pricing models"):
    |
    |  - 'global' (default, backward compat z blizne-art-cms):
    |      total_amount = rental_type.price * qty
    |      Cena zalezy od typu wynajmu, nie od konkretnego rentable.
    |
    |  - 'per_rentable' (dodane dla 2wheels-rental.pl, KML / STR-0008):
    |      total_amount = rentable.{rental_type.meta.rentable_field}
    |                     * countUnits(start, end, rental_type.meta.unit)
    |                     * qty
    |      Cena pochodzi z modelu Rentable (np. motorcycle.price_per_day).
    |
    | Aplikacja moze tez zarejestrowac wlasna strategie:
    |   $this->app->singleton(PricingStrategyInterface::class, MyStrategy::class);
    |
    */
    'pricing_strategy' => env('RENTAL_PRICING_STRATEGY', 'global'),

    'pricing_strategies' => [
        'global' => GlobalPricingStrategy::class,
        'per_rentable' => PerRentablePricingStrategy::class,
        'tiered_per_rentable' => TieredPerRentablePricingStrategy::class,
    ],

    /*
    | Domyslne meta dla per_rentable strategy gdy RentalType.meta jest puste.
    | Aplikacja moze nadpisac ENV-em np. dla biznesu gdzie pole nazywa sie inaczej.
    */
    'pricing_default_unit' => env('RENTAL_PRICING_DEFAULT_UNIT', 'day'),
    'pricing_default_rentable_field' => env('RENTAL_PRICING_DEFAULT_RENTABLE_FIELD', 'price_per_day'),

    /*
    |--------------------------------------------------------------------------
    | URL frontendu (dla redirectow po platnosci)
    |--------------------------------------------------------------------------
    |
    | Bazowy URL aplikacji frontendowej (Next.js, Vue itp.) uzywany do
    | przekierowania klienta po zakonczeniu platnosci na strony statusu:
    |   {frontend_url}/rezerwacja/sukces?id={rentalId}
    |   {frontend_url}/rezerwacja/blad?id={rentalId}
    |
    | Jesli null/pusty, MockP24Controller wraca do strony checkout z parametrem
    | ?status=success|failure (przydatne w testach lokalnych).
    |
    */
    'frontend_url' => env('RENTAL_FRONTEND_URL'),

    /*
    |--------------------------------------------------------------------------
    | Email potwierdzajacy rezerwacje (C3)
    |--------------------------------------------------------------------------
    |
    | Czy RentalService::markPaid() i RentalService::confirm() maja wysylac
    | mail RentalConfirmed do klienta. Mail jest queueable (ShouldQueue),
    | wiec aplikacja powinna miec skonfigurowany queue worker.
    |
    | Aplikacja moze nadpisac Mailable lub view:
    |   php artisan vendor:publish --tag=rental-views
    |
    */
    'send_confirmation_email' => env('RENTAL_SEND_CONFIRMATION_EMAIL', true),

];
