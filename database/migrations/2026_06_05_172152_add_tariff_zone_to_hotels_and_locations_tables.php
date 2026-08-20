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
        Schema::table('hotels', function (Blueprint $table) {
            $table->string('tariff_zone')->nullable()->after('location_id')->index();
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->string('tariff_zone')->nullable()->after('city_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn('tariff_zone');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('tariff_zone');
        });
    }
};
