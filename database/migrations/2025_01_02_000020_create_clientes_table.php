<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('codcliente', 20)->unique();
            $table->string('codcontabilidad', 20)->nullable();
            $table->string('nombretotal', 150)->nullable();
            $table->string('nombre', 80)->nullable();
            $table->string('apellido', 80)->nullable();
            $table->string('identificacion', 20)->nullable();
            $table->string('dni', 20)->nullable();
            $table->string('tipo', 20)->nullable();
            $table->string('sexo', 10)->nullable();
            $table->string('domicilio', 255)->nullable();
            $table->string('poblacion', 80)->nullable();
            $table->string('codigopostal', 10)->nullable();
            $table->string('provincia', 60)->nullable();
            $table->string('pais', 60)->nullable();
            $table->string('nacionalidad', 60)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('fax', 20)->nullable();
            $table->string('movil', 20)->nullable();
            $table->string('trabajo', 20)->nullable();
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
            $table->boolean('domiciliado')->default(false);
            $table->timestamps();

            $table->index('email');
            $table->index('dni');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
