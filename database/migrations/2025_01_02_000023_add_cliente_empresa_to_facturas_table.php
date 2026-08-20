<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            if (! Schema::hasColumn('facturas', 'cliente_id')) {
                $table->foreignId('cliente_id')->nullable()->after('id')->constrained('clientes')->nullOnDelete();
            }
            if (! Schema::hasColumn('facturas', 'empresa_id')) {
                $table->foreignId('empresa_id')->nullable()->after('cliente_id')->constrained('empresas')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            if (Schema::hasColumn('facturas', 'cliente_id')) {
                $table->dropConstrainedForeignId('cliente_id');
            }
            if (Schema::hasColumn('facturas', 'empresa_id')) {
                $table->dropConstrainedForeignId('empresa_id');
            }
        });
    }
};
