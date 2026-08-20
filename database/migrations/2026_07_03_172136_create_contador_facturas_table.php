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
        if (! Schema::hasTable('contador_facturas')) {
            Schema::create('contador_facturas', function (Blueprint $table) {
                $table->id();
                $table->integer('contador')->default(0);
                $table->integer('ano')->default(0);
                $table->timestamps();

                $table->index('ano');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contador_facturas');
    }
};
