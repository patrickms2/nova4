<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transfer_tariffs', function (Blueprint $table) {
            $table->id();
            $table->string('origin_zone');
            $table->string('destination_zone');
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->unsignedTinyInteger('holiday_surcharge_percent')->default(15);
            $table->unsignedTinyInteger('igic_percent')->default(7);
            $table->boolean('igic_included')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['origin_zone', 'destination_zone']);
            $table->index(['origin_zone', 'destination_zone', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_tariffs');
    }
};
