<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabela rentals - rezerwacje.
 *
 * Port z blizne-art-cms/2026_04_05_000003_create_reservations_table.
 *
 * Roznice:
 *  - id: UUID (zachowane z Reservation)
 *  - time_slot_id -> rentable polimorficznie + zakres start_date/end_date
 *    (KML-0046 - wynajem motocykla to przedzial dat, nie pojedynczy slot)
 *  - ticket_type_id -> rental_type_id (FK do rental_types)
 *  - guests -> qty (generyczne, np. liczba sztuk sprzetu)
 *  - usunieto tour_type/tour_language (specyficzne dla wycieczek
 *    blizne-art-cms, nieadekwatne dla generycznego wynajmu)
 *  - dodano: currency, locale, meta (json)
 *
 * Statusy: pending, confirmed, paid, cancelled, expired
 */
return new class extends Migration
{
    public function up(): void
    {
      
    }

    public function down(): void
    {
        Schema::dropIfExists(config('rental.tables.rentals', 'rentals'));
    }
};
