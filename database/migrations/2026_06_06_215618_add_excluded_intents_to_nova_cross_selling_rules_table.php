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
        Schema::table('nova_cross_selling_rules', function (Blueprint $table) {
            $table->json('excluded_intents')->nullable()->after('cta_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nova_cross_selling_rules', function (Blueprint $table) {
            $table->dropColumn('excluded_intents');
        });
    }
};
