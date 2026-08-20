<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registros_facturas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('factura_id');
            $table->string('codfactura', 50)->nullable();
            $table->string('unidad', 20)->nullable();
            $table->string('descripcion', 255)->nullable();
            $table->decimal('cantidad', 15, 2)->default(1);
            $table->decimal('precio', 15, 2)->default(0);
            $table->decimal('descuento', 15, 2)->default(0);
            $table->decimal('valorimpuesto', 15, 2)->default(0);
            $table->decimal('valorretenciones', 15, 2)->default(0);
            $table->decimal('importe', 15, 2)->default(0);
            $table->date('fecha')->nullable();
            $table->timestamps();

            $table->foreign('factura_id')->references('id')->on('facturas')->onDelete('cascade');
            $table->index('codfactura');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_facturas');
    }
};
