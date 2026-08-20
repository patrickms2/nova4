<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->string('codfactura', 50);
            $table->string('codeempresa', 50)->default('DEFAULT');
            $table->unsignedBigInteger('codcliente')->nullable();
            $table->string('cliente_nombre')->nullable();
            $table->string('cliente_cif')->nullable();
            $table->string('cliente_direccion')->nullable();
            $table->string('cliente_telefono')->nullable();
            $table->date('fechaemitido')->nullable();
            $table->decimal('baseimponible', 15, 2)->nullable();
            $table->decimal('baseexenta', 15, 2)->nullable();
            $table->decimal('impuesto', 15, 2)->nullable();
            $table->decimal('retenciones', 15, 2)->nullable();
            $table->decimal('importe', 15, 2)->nullable();
            $table->boolean('pagada')->default(false);
            $table->text('observaciones')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['codfactura', 'codeempresa']);
            $table->index('codcliente');
            $table->index('fechaemitido');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
