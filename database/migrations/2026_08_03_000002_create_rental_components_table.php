<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_components', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rental_reservation_id')->constrained('rental_reservations')->cascadeOnDelete();
            $table->string('type');
            $table->string('label');
            $table->decimal('amount', 10, 2)->default(0);
            $table->boolean('generates_commission')->default(false);
            $table->boolean('is_income')->default(false);
            $table->boolean('is_expense')->default(false);
            $table->string('provider_name')->nullable();
            $table->string('status')->default('estimated');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_components');
    }
};
