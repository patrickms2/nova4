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
        Schema::create('nova_taxi_route_drafts', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique();
            $table->string('tourist_phone')->nullable()->index();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('origin');
            $table->string('destination');
            $table->date('pickup_date');
            $table->time('pickup_time');
            $table->unsignedSmallInteger('passengers')->default(1);
            $table->string('status')->default('pending_payment')->index();
            $table->string('chbs_url', 1000);
            $table->string('external_booking_id')->nullable()->index();
            $table->string('woo_order_id')->nullable()->index();
            $table->json('conversation')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nova_taxi_route_drafts');
    }
};
