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
        Schema::table('conceptos', function (Blueprint $table) {
            if (! Schema::hasColumn('conceptos', 'cliente_id')) {
                $table->foreignId('cliente_id')->nullable()->after('id')->constrained('clientes')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conceptos', function (Blueprint $table) {
            if (Schema::hasColumn('conceptos', 'cliente_id')) {
                $table->dropConstrainedForeignId('cliente_id');
            }
        });
    }
};
