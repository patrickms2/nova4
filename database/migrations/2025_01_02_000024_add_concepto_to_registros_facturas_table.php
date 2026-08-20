<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registros_facturas', function (Blueprint $table) {
            if (! Schema::hasColumn('registros_facturas', 'concepto_id')) {
                $table->foreignId('concepto_id')->nullable()->after('factura_id')->constrained('conceptos')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('registros_facturas', function (Blueprint $table) {
            if (Schema::hasColumn('registros_facturas', 'concepto_id')) {
                $table->dropConstrainedForeignId('concepto_id');
            }
        });
    }
};
