<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nova_ai_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('nova_business_id')->constrained('nova_businesses')->cascadeOnDelete();
            $table->string('name');
            $table->string('provider')->default('openai')->index();
            $table->string('model')->default('gpt-4.1-mini');
            $table->string('status')->default('draft')->index();
            $table->text('system_prompt')->nullable();
            $table->decimal('temperature', 3, 2)->default(0.30);
            $table->unsignedInteger('max_tokens')->nullable();
            $table->json('tools_policy')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['nova_business_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nova_ai_profiles');
    }
};
