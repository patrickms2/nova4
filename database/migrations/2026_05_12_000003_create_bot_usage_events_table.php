<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        if ($this->schema()->hasTable('bot_usage_events')) {
            return;
        }

        $this->schema()->create('bot_usage_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bot_id')->constrained('agentic_bots')->cascadeOnDelete();
            $table->foreignId('bot_access_token_id')->nullable()->constrained('bot_access_tokens')->nullOnDelete();
            $table->foreignId('bot_conversation_id')->nullable()->constrained('bot_conversations')->nullOnDelete();
            $table->foreignId('workflow_run_id')->nullable()->constrained('workflow_runs')->nullOnDelete();
            $table->string('source', 32)->default('chat');
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('reasoning_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->decimal('estimated_cost_cents', 12, 4)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index(['bot_id', 'occurred_at']);
            $table->index(['bot_access_token_id', 'occurred_at'], 'bot_usage_events_token_occurred_index');
        });
    }

    public function down(): void
    {
        $this->schema()->dropIfExists('bot_usage_events');
    }
};
