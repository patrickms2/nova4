<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('codeempresa')->unique();
            $table->string('empresa', 150)->nullable();
            $table->string('nif', 20)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->string('codigopostal', 15)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('fax', 20)->nullable();
            $table->string('web', 80)->nullable();
            $table->string('email', 80)->nullable();
            $table->string('cuentacorriente', 40)->nullable();
            $table->string('tarjetacredito', 40)->nullable();
            $table->string('tipocredito', 40)->nullable();
            $table->date('fechaalta')->nullable();
            $table->date('fechamodificado')->nullable();
            $table->date('fechafacturado')->nullable();
            $table->date('fechabaja')->nullable();
            $table->unsignedBigInteger('usuario')->nullable();
            $table->string('observaciones', 255)->nullable();
            $table->string('logoempresa', 150)->nullable();
            $table->string('logopublicidad', 150)->nullable();
            $table->string('administrador', 150)->nullable();
            $table->string('poblacion', 150)->nullable();
            $table->decimal('porcentajeexplotacion', 15, 2)->nullable();
            $table->timestamps();

            $table->index('empresa');
            $table->index('nif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
