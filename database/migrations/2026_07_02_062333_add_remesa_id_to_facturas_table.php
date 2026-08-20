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
        Schema::table('facturas', function (Blueprint $table) {
            if (! Schema::hasColumn('facturas', 'remesa_id')) {
                $table->foreignId('remesa_id')->nullable()->after('empresa_id')->constrained('remesas')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            if (Schema::hasColumn('facturas', 'remesa_id')) {
                $table->dropConstrainedForeignId('remesa_id');
            }
        });
    }
};
