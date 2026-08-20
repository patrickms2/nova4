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
        Schema::create('public_booking_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('public_booking_request_id')->constrained()->cascadeOnDelete();
            $table->string('item_type');
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('service_name')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->dateTime('starts_at')->nullable();
            $table->json('metadata')->nullable();
            $table->string('remote_booking_status')->nullable();
            $table->string('remote_source_platform')->nullable();
            $table->string('remote_external_id')->nullable();
            $table->timestamps();

            $table->index(['item_type', 'starts_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_booking_request_items');
    }
};
