<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conceptos', function (Blueprint $table) {
            $table->id();
            $table->string('codconcepto', 20)->unique();
            $table->string('concepto', 100);
            $table->string('grupo', 40)->nullable();
            $table->string('unidad', 20)->nullable();
            $table->decimal('precio', 15, 2)->default(0);
            $table->decimal('descuento', 15, 2)->default(0);
            $table->decimal('impuesto', 15, 2)->default(7.00);   // IGIC 7%
            $table->decimal('retenciones', 15, 2)->default(15.00); // 15%
            $table->integer('unidadminimo')->nullable();
            $table->string('observaciones', 255)->nullable();
            $table->string('codempresa', 20)->nullable();
            $table->string('categoria', 50)->nullable(); // alojamiento, restauracion, transporte...
            $table->timestamps();

            $table->index('categoria');
            $table->index('grupo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conceptos');
    }
};
