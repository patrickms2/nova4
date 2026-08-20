<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabela rental_types - typy wynajmu / pakiety.
 *
 * Port z blizne-art-cms/2026_04_05_000001_create_ticket_types_table.
 *
 * Roznice:
 *  - generyczna nazwa (rental_types vs ticket_types)
 *  - dodano: currency (kod waluty na poziomie pakietu, opcjonalny override)
 *  - dodano: meta (json) na elastyczne dane per-aplikacja
 */
return new class extends Migration
{
    public function up(): void
    {
        $table = config('rental.tables.rental_types', 'rental_types');

        Schema::create($table, function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('label');
            $t->string('label_en')->nullable();
            $t->unsignedInteger('price')->comment('Cena w najmniejszych jednostkach (np. grosze)');
            $t->string('currency', 3)->default('PLN');
            $t->text('description')->nullable();
            $t->text('description_en')->nullable();
            $t->date('available_from')->nullable();
            $t->date('available_until')->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('sort_order')->default(0);
            $t->json('meta')->nullable();
            $t->timestamps();

            $t->index('is_active');
            $t->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('rental.tables.rental_types', 'rental_types'));
    }
};
