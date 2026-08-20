<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * KML-0047: dodaje datetime start_at/end_at do rentals.
 *
 * Wymaganie biznesowe (L0): rezerwacja musi obslugiwac godzine odbioru i godzine zwrotu.
 * Od X:00 dnia A do X:00 dnia B = pelna doba; kazda godzina po starcie kolejnego dnia
 * rozpoczyna kolejna dobe (zaokraglenie ceil(diffH/24)).
 *
 * Backfill: istniejace rezerwacje dostaja godzine 10:00:00 (default biznesowy).
 * Pola zostaja nullable po backfill - osobna migracja zrobi NOT NULL po weryfikacji.
 *
 * start_date/end_date pozostawione dla wstecznej kompatybilnosci raportow Filamentowych
 * (sa synchronizowane przez Rental::saving() event listener).
 */
return new class extends Migration
{
    public function up(): void
    {
  
    }

    public function down(): void
    {
        $table = config('rental.tables.rentals', 'rentals');

        Schema::table($table, function (Blueprint $t) {
            $t->dropIndex('rentals_at_idx');
            $t->dropColumn(['start_at', 'end_at']);
        });
    }
};
