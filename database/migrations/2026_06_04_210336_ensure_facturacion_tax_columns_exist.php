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
        Schema::table('registros_facturas', function (Blueprint $table) {
            if (! Schema::hasColumn('registros_facturas', 'impuesto')) {
                $table->decimal('impuesto', 15, 2)->default(0)->after('descuento');
            }

            if (! Schema::hasColumn('registros_facturas', 'retenciones')) {
                $table->decimal('retenciones', 15, 2)->default(0)->after('valorimpuesto');
            }
        });

        Schema::table('facturas', function (Blueprint $table) {
            if (! Schema::hasColumn('facturas', 'notas')) {
                $table->text('notas')->nullable()->after('observaciones');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            if (Schema::hasColumn('facturas', 'notas')) {
                $table->dropColumn('notas');
            }
        });

        Schema::table('registros_facturas', function (Blueprint $table) {
            if (Schema::hasColumn('registros_facturas', 'retenciones')) {
                $table->dropColumn('retenciones');
            }

            if (Schema::hasColumn('registros_facturas', 'impuesto')) {
                $table->dropColumn('impuesto');
            }
        });
    }
};
