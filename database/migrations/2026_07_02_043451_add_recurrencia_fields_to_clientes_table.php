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
        Schema::table('clientes', function (Blueprint $table) {
            if (! Schema::hasColumn('clientes', 'recurrencia_dia')) {
                $table->unsignedTinyInteger('recurrencia_dia')->default(1)->after('domiciliado');
            }
            if (! Schema::hasColumn('clientes', 'recurrencia_activa')) {
                $table->boolean('recurrencia_activa')->default(false)->after('recurrencia_dia');
            }
            if (! Schema::hasColumn('clientes', 'recurrencia_notas')) {
                $table->string('recurrencia_notas', 255)->nullable()->after('recurrencia_activa');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            if (Schema::hasColumn('clientes', 'recurrencia_notas')) {
                $table->dropColumn('recurrencia_notas');
            }
            if (Schema::hasColumn('clientes', 'recurrencia_activa')) {
                $table->dropColumn('recurrencia_activa');
            }
            if (Schema::hasColumn('clientes', 'recurrencia_dia')) {
                $table->dropColumn('recurrencia_dia');
            }
        });
    }
};
