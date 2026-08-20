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
        Schema::create('nova_bundle_orders', function (Blueprint $table) {
            $table->id();
            $table->string('bundle_reference', 120)->unique();
            $table->string('status', 30)->default('pending');
            $table->json('customer_data');
            $table->unsignedBigInteger('la_geria_order_id')->nullable();
            $table->string('la_geria_order_number', 50)->nullable();
            $table->string('la_geria_status', 30)->nullable();
            $table->decimal('la_geria_total', 12, 2)->nullable();
            $table->string('lanzaloe_order_id', 120)->nullable();
            $table->string('lanzaloe_cart_id', 120)->nullable();
            $table->string('lanzaloe_status', 30)->nullable();
            $table->text('lanzaloe_error')->nullable();
            $table->json('raw_result')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nova_bundle_orders');
    }
};
