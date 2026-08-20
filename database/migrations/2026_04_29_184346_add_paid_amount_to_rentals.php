<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dodaje kolumne paid_amount do tabeli rentals.
 *
 * paid_amount przechowuje kwote faktycznie pobrana przez bramke platnosci
 * (P24) w groszach. Pozwala odroznic kwote rezerwacji (total_amount,
 * mozliwa do zmiany przy zmianie motocykla) od kwoty wplaconej przez
 * klienta. Roznica = saldo do rozliczenia w biurze (KML-0047 ext).
 *
 * @see KML-0047 (rozszerzenie: rozliczenie w biurze)
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
            $t->dropColumn('paid_amount');
        });
    }
};
