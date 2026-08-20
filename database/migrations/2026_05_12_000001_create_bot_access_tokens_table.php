<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        if ($this->schema()->hasTable('bot_access_tokens')) {
            return;
        }

        $this->schema()->create('bot_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bot_id')->constrained('agentic_bots')->cascadeOnDelete();
            $table->string('name');
            $table->string('token_hash', 64)->unique();
            $table->string('token_prefix', 16)->index();
            $table->json('allowed_areas')->nullable();
            $table->json('abilities')->nullable();
            $table->unsignedInteger('rate_limit_per_minute')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['bot_id', 'is_active']);
        });
    }

    public function down(): void
    {
        $this->schema()->dropIfExists('bot_access_tokens');
    }
};
