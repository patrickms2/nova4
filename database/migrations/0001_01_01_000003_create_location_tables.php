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
        // Countries table
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->notNull();
            $table->string('code')->notNull();
            $table->string('continent_code')->nullable();
            $table->string('phone_code')->nullable();
            $table->boolean('is_active')->default(true);
        });

        // Cities table
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('countries');
            $table->string('name')->notNull();
            $table->boolean('is_popular')->default(false);
            $table->text('description')->nullable();
        });

        // Locations table
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->notNull();
            $table->foreignId('city_id')->constrained('cities');
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_popular')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('countries');
    }
};
