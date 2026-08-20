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
        // Restaurants table
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id('id');
            $table->string('restaurant_name')->notNull();
            $table->text('description')->nullable();
            $table->foreignId('location_id')->constrained('locations', 'id');
            $table->string('cuisine')->nullable();
            $table->integer('price_range')->nullable()->comment('1=Inexpensive, 2=Moderate, 3=Expensive, 4=Very Expensive');
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('total_ratings')->default(0);
            $table->string('main_image_url')->nullable();
            $table->string('website')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('has_reservation')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->foreignId('manager_id')->nullable()->constrained('users', 'id');
            $table->timestamps();
        });

        // Restaurant Images table
        Schema::create('restaurant_images', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('restaurant_id')->constrained('restaurants', 'id');
            $table->string('image_url')->notNull();
            $table->integer('display_order')->default(0);
            $table->string('caption')->nullable();
            $table->boolean('is_active')->default(true);
        });

        // Menu Categories table
        Schema::create('menu_categories', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('restaurant_id')->constrained('restaurants', 'id');
            $table->string('category_name')->notNull();
            $table->text('description')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
        });

        // Menu Items table
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('category_id')->constrained('menu_categories', 'id');
            $table->string('item_name')->notNull();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->notNull();
            $table->boolean('is_vegetarian')->default(false);
            $table->boolean('is_vegan')->default(false);
            $table->boolean('is_gluten_free')->default(false);
            $table->integer('spiciness')->nullable()->comment('0=Not Spicy, 1=Mild, 2=Medium, 3=Hot');
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
        });

        // Restaurant Tables table
        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('restaurant_id')->constrained('restaurants', 'id');
            $table->string('table_number')->notNull();
            $table->integer('capacity')->notNull();
            $table->string('location')->nullable()->comment('Indoor, Outdoor, Private');
            $table->boolean('is_active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurant_tables');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menu_categories');
        Schema::dropIfExists('restaurant_images');
        Schema::dropIfExists('restaurants');
    }
};
