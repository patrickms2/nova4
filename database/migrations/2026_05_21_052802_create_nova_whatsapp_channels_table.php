<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nova_whatsapp_channels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('nova_business_id')->constrained('nova_businesses')->cascadeOnDelete();
            $table->string('name');
            $table->string('provider')->default('meta')->index();
            $table->string('phone_number')->nullable();
            $table->string('phone_number_id')->nullable();
            $table->string('business_account_id')->nullable();
            $table->string('webhook_url')->nullable();
            $table->string('status')->default('draft')->index();
            $table->json('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['nova_business_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nova_whatsapp_channels');
    }
};
