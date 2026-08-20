<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_properties', function (Blueprint $table): void {
            $table->json('financial_settings')->nullable()->after('settings');
        });
    }

    public function down(): void
    {
        Schema::table('rental_properties', function (Blueprint $table): void {
            $table->dropColumn('financial_settings');
        });
    }
};
