<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_settlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rental_reservation_id')->constrained('rental_reservations')->cascadeOnDelete();
            $table->string('status')->default('estimated');
            $table->decimal('accommodation_amount', 10, 2)->default(0);
            $table->decimal('channel_commission_amount', 10, 2)->default(0);
            $table->decimal('commissionable_base', 10, 2)->default(0);
            $table->decimal('manager_commission_amount', 10, 2)->default(0);
            $table->decimal('services_amount', 10, 2)->default(0);
            $table->decimal('estimated_net', 10, 2)->default(0);
            $table->decimal('real_payout', 10, 2)->nullable();
            $table->decimal('difference', 10, 2)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_settlements');
    }
};
