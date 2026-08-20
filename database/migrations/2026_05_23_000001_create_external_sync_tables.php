<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('business_name')->nullable();
            $table->string('source_platform');
            $table->string('source_label');
            $table->string('connection_type')->default('api');
            $table->string('base_url')->nullable();
            $table->string('api_url')->nullable();
            $table->string('external_db_connection')->nullable();
            $table->string('external_db_driver')->nullable();
            $table->string('external_db_host')->nullable();
            $table->string('external_db_port')->nullable();
            $table->string('external_db_database')->nullable();
            $table->string('external_db_username')->nullable();
            $table->text('external_db_password')->nullable();
            $table->string('external_db_prefix')->nullable();
            $table->json('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('last_sync_started_at')->nullable();
            $table->timestamp('last_sync_finished_at')->nullable();
            $table->timestamp('last_sync_failed_at')->nullable();
            $table->text('last_sync_error')->nullable();
            $table->timestamps();

            $table->unique(['server_id', 'source_platform', 'source_label']);
            $table->index(['source_platform', 'status']);
        });

        Schema::create('external_catalog_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('external_source_id')->constrained('external_sources')->cascadeOnDelete();
            $table->string('business_name')->nullable();
            $table->string('source_platform');
            $table->string('source_label');
            $table->string('external_id');
            $table->string('external_item_id')->nullable();
            $table->string('type')->default('product');
            $table->string('status')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->string('sku')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('regular_price', 12, 2)->nullable();
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('booking_url')->nullable();
            $table->string('purchase_url')->nullable();
            $table->string('admin_url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->string('source_fingerprint')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['source_platform', 'external_id', 'external_item_id'], 'external_catalog_source_item_unique');
            $table->index(['server_id', 'source_platform']);
            $table->index(['business_name', 'type']);
        });

        Schema::create('external_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('external_source_id')->constrained('external_sources')->cascadeOnDelete();
            $table->string('business_name')->nullable();
            $table->string('source_platform');
            $table->string('source_label');
            $table->string('external_id');
            $table->string('external_item_id')->nullable();
            $table->string('intent_key')->nullable();
            $table->string('booking_type')->default('order');
            $table->string('status')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('service_name')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('party_size')->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->decimal('total', 12, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('admin_url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->string('source_fingerprint')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['source_platform', 'external_id', 'external_item_id'], 'external_bookings_source_item_unique');
            $table->index(['server_id', 'booking_type']);
            $table->index(['business_name', 'status']);
        });

        Schema::create('external_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('external_source_id')->constrained('external_sources')->cascadeOnDelete();
            $table->string('business_name')->nullable();
            $table->string('source_platform');
            $table->string('source_label');
            $table->string('external_id');
            $table->string('external_increment_id')->nullable();
            $table->string('status')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->decimal('subtotal', 12, 2)->nullable();
            $table->decimal('tax_amount', 12, 2)->nullable();
            $table->decimal('shipping_amount', 12, 2)->nullable();
            $table->decimal('discount_amount', 12, 2)->nullable();
            $table->decimal('grand_total', 12, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('payment_method')->nullable();
            $table->string('shipping_method')->nullable();
            $table->timestamp('ordered_at')->nullable();
            $table->json('items')->nullable();
            $table->string('admin_url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->string('source_fingerprint')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['source_platform', 'external_id']);
            $table->index(['server_id', 'status']);
            $table->index(['business_name', 'payment_status']);
        });

        Schema::create('external_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_source_id')->nullable()->constrained('external_sources')->nullOnDelete();
            $table->foreignId('server_id')->nullable()->constrained()->nullOnDelete();
            $table->string('command');
            $table->string('sync_type')->default('mixed');
            $table->string('status')->default('completed');
            $table->unsignedInteger('processed')->default(0);
            $table->unsignedInteger('created')->default(0);
            $table->unsignedInteger('updated')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->json('summary')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['server_id', 'created_at']);
            $table->index(['external_source_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_sync_logs');
        Schema::dropIfExists('external_orders');
        Schema::dropIfExists('external_bookings');
        Schema::dropIfExists('external_catalog_items');
        Schema::dropIfExists('external_sources');
    }
};
