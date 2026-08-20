<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('external_source_id')->constrained('external_sources')->cascadeOnDelete();

            $table->string('business_name')->nullable();
            $table->string('source_platform');
            $table->string('source_label');

            $table->string('external_id');
            $table->string('external_token')->nullable();
            $table->string('external_receipt_number')->nullable();

            // LatePoint stores booking id in order_id; we keep it to link to bookings later.
            $table->string('external_order_id')->nullable();
            $table->string('external_booking_id')->nullable();
            $table->string('external_service_id')->nullable();
            $table->string('service_name')->nullable();

            $table->string('resource_type')->nullable();
            $table->string('target_model')->nullable();

            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();

            $table->string('processor')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('kind')->nullable();
            $table->string('status')->nullable();

            $table->decimal('amount', 12, 2)->nullable();
            $table->string('currency', 3)->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('source_updated_at')->nullable();
            $table->string('source_fingerprint')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['source_platform', 'external_id'], 'external_payments_source_unique');
            $table->index(['server_id', 'paid_at']);
            $table->index(['business_name', 'processor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_payments');
    }
};
