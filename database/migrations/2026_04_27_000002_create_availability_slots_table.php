<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabela availability_slots - sloty blokady dostepnosci dla zasobu Rentable.
 *
 * Adaptacja z blizne-art-cms/2026_04_05_000002_create_time_slots_table.
 *
 * Roznice:
 *  - polimorficzna relacja do Rentable (rentable_type, rentable_id)
 *    zamiast pojedynczego time slotu
 *  - zakres (start_date/end_date) zamiast pojedynczej daty + godziny
 *    (wynajem motocykla to dni, nie godziny)
 *  - is_blocked + reason zamiast max_guests/booked_guests
 *    (model "ekskluzywny" - slot to calkowita blokada, nie wieloosobowa
 *     rezerwacja jak wycieczka)
 *
 * Use case:
 *  - Admin oznacza "motocykl Ducati w serwisie 5-10 maja" -> blokada
 *  - System rezerwacji uzywa overlap query do wykluczenia konfliktow
 */
return new class extends Migration
{
    public function up(): void
    {
     
    }

    public function down(): void
    {
        Schema::dropIfExists(config('rental.tables.availability_slots', 'availability_slots'));
    }
};
