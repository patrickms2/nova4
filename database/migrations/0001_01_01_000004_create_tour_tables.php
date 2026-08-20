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
        // tour_categories table
        Schema::create('tour_categories', function (Blueprint $table) {
            $table->id('id');
            $table->string('category_name')->notNull();
            $table->text('description')->nullable();
            $table->foreignId('parent_category_id')->nullable()->constrained('tour_categories', 'id')->nullOnDelete();
            $table->string('icon_url')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
        });

        // tours table
        Schema::create('tours', function (Blueprint $table) {
            $table->id('id');
            $table->string('tour_name')->notNull();
            $table->text('description')->nullable();
            $table->string('short_description')->nullable();
            $table->foreignId('location_id')->constrained('locations', 'id');
            $table->decimal('duration_hours', 5, 2)->nullable();
            $table->integer('duration_days')->nullable();
            $table->decimal('base_price', 10, 2)->notNull();
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->integer('max_capacity')->notNull();
            $table->integer('min_participants')->default(1);
            $table->enum('difficulty_level', ['Easy', 'Moderate', 'Difficult'])->default('Easy');
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('total_ratings')->default(0);
            $table->string('main_image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->foreignId('created_by')->constrained('users', 'id');
            $table->timestamps();
        });

        // tour_images table
        Schema::create('tour_images', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('tour_id')->constrained('tours', 'id');
            $table->string('image_url')->notNull();
            $table->integer('display_order')->default(0);
            $table->string('caption')->nullable();
            $table->boolean('is_active')->default(true);
        });

        // tour_category_mapping table
        Schema::create('tour_category_mapping', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('tour_id')->constrained('tours', 'id');
            $table->foreignId('category_id')->constrained('tour_categories', 'id');
            $table->unique(['tour_id', 'category_id']);
        });

        // tour_schedules table
        Schema::create('tour_schedules', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('tour_id')->constrained('tours', 'id');
            $table->date('start_date')->notNull();
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->integer('available_spots')->notNull();
            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('TourSchedules');
        Schema::dropIfExists('TourCategoryMapping');
        Schema::dropIfExists('TourImages');
        Schema::dropIfExists('Tours');
        Schema::dropIfExists('TourCategories');
    }
};
