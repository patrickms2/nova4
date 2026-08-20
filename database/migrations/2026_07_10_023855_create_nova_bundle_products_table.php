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
        Schema::create('nova_bundle_products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->string('la_geria_product_id', 120);
            $table->string('la_geria_product_name', 255)->nullable();
            $table->unsignedInteger('la_geria_quantity')->default(1);
            $table->decimal('la_geria_unit_price', 10, 2)->nullable();
            $table->string('lanzaloe_sku', 120);
            $table->string('lanzaloe_product_name', 255)->nullable();
            $table->unsignedInteger('lanzaloe_quantity')->default(1);
            $table->decimal('lanzaloe_unit_price', 10, 2)->nullable();
            $table->decimal('total_price', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nova_bundle_products');
    }
};
