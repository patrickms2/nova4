<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_properties', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained('properties')->nullOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('nickname')->nullable();
            $table->text('address')->nullable();
            $table->string('tourist_registry')->nullable();
            $table->string('cadastral_reference')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('rental_guests', function (Blueprint $table): void {
            $table->id();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('country')->nullable();
            $table->string('document_number')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('avatar_url')->nullable();
            $table->timestamps();
        });

        Schema::create('rental_reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rental_property_id')->constrained('rental_properties')->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained('rental_guests');
            $table->string('channel');
            $table->string('reference_code')->nullable();
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedTinyInteger('adults')->default(1);
            $table->unsignedTinyInteger('children')->default(0);
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('channel_commission', 10, 2)->default(0);
            $table->decimal('management_commission', 10, 2)->default(0);
            $table->decimal('cleaning_fee', 10, 2)->default(0);
            $table->decimal('payout', 10, 2)->default(0);
            $table->string('status')->default('confirmed');
            $table->json('raw_payload')->nullable();
            $table->dateTime('parsed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('rental_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rental_reservation_id')->constrained('rental_reservations')->cascadeOnDelete();
            $table->string('source');
            $table->decimal('amount', 10, 2);
            $table->date('paid_at')->nullable();
            $table->date('expected_at')->nullable();
            $table->string('status')->default('pending');
            $table->string('transaction_id')->nullable();
            $table->timestamps();
        });

        Schema::create('rental_expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rental_property_id')->constrained('rental_properties')->cascadeOnDelete();
            $table->string('category');
            $table->string('provider_name')->nullable();
            $table->string('description');
            $table->decimal('base_amount', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->date('expense_date');
            $table->string('status')->default('pending');
            $table->boolean('is_recurrent')->default(false);
            $table->string('document_path')->nullable();
            $table->timestamps();
        });

        Schema::create('rental_documents', function (Blueprint $table): void {
            $table->id();
            $table->morphs('documentable');
            $table->string('category');
            $table->string('title');
            $table->string('file_path');
            $table->date('expiry_date')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('rental_contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rental_property_id')->constrained('rental_properties')->cascadeOnDelete();
            $table->string('category');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('rental_inventory_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rental_property_id')->constrained('rental_properties')->cascadeOnDelete();
            $table->string('category');
            $table->string('location')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_value', 10, 2)->nullable();
            $table->date('warranty_expires_at')->nullable();
            $table->string('status')->default('active');
            $table->string('qr_code')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });

        Schema::create('rental_incidents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rental_property_id')->constrained('rental_properties')->cascadeOnDelete();
            $table->foreignId('rental_reservation_id')->nullable()->constrained('rental_reservations')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('open');
            $table->string('priority')->default('medium');
            $table->string('assignee_name')->nullable();
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->decimal('final_cost', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('rental_timeline_events', function (Blueprint $table): void {
            $table->id();
            $table->morphs('subject');
            $table->string('event_type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_timeline_events');
        Schema::dropIfExists('rental_incidents');
        Schema::dropIfExists('rental_inventory_items');
        Schema::dropIfExists('rental_contacts');
        Schema::dropIfExists('rental_documents');
        Schema::dropIfExists('rental_expenses');
        Schema::dropIfExists('rental_payments');
        Schema::dropIfExists('rental_reservations');
        Schema::dropIfExists('rental_guests');
        Schema::dropIfExists('rental_properties');
    }
};
