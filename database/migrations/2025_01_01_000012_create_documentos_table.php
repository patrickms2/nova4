<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('factura_id')->nullable();
            $table->string('nombre')->nullable();
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('extension', 10)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedInteger('pages')->nullable();
            $table->string('tipo')->nullable(); // factura, nomina, etc.
            $table->timestamp('processed_at')->nullable();
            $table->string('import_log_path')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('factura_id')->references('id')->on('facturas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
