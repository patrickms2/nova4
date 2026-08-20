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
        // travel_agencies table
        Schema::create('travel_agencies', function (Blueprint $table) {
            $table->id('id');
            $table->string('agency_name')->notNull();
            $table->text('description')->nullable();
            $table->foreignId('location_id')->constrained('locations', 'id');
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('total_ratings')->default(0);
            $table->string('logo_url')->nullable();
            $table->string('website')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('manager_id')->nullable()->constrained('users', 'id');
            $table->timestamps();
        });

        // travel_packages table
        Schema::create('travel_packages', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('agency_id')->constrained('travel_agencies', 'id');
            $table->string('package_name')->notNull();
            $table->text('description')->nullable();
            $table->integer('duration_days')->notNull();
            $table->decimal('base_price', 10, 2)->notNull();
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->integer('max_participants')->nullable();
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('total_ratings')->default(0);
            $table->string('main_image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });

        // package_destinations table
        Schema::create('package_destinations', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('package_id')->constrained('travel_packages', 'id');
            $table->foreignId('location_id')->constrained('locations', 'id');
            $table->integer('day_number')->notNull();
            $table->text('description')->nullable();
            $table->string('duration')->nullable();
        });

        // package_inclusions table
        Schema::create('package_inclusions', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('package_id')->constrained('travel_packages', 'id');
            $table->enum('inclusion_type', ['Tour', 'Hotel', 'Transport', 'Meal', 'Other'])->notNull();
            $table->string('description')->notNull();
            $table->boolean('is_highlighted')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('PackageInclusions');
        Schema::dropIfExists('PackageDestinations');
        Schema::dropIfExists('TravelPackages');
        Schema::dropIfExists('TravelAgencies');
    }
};
