<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        if ($this->schema()->hasTable('bot_action_executions')) {
            return;
        }

        $this->ensureTablesExist([
            'agentic_bots',
            'bot_conversations',
            'workflow_runs',
        ], 'create bot_action_executions');

        $this->schema()->create('bot_action_executions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bot_id')->constrained('agentic_bots')->cascadeOnDelete();
            $table->foreignId('bot_conversation_id')->nullable()->constrained('bot_conversations')->nullOnDelete();
            $table->foreignId('workflow_run_id')->nullable()->constrained('workflow_runs')->nullOnDelete();
            $table->string('action_key', 128);
            $table->string('business_key', 128);
            $table->string('input_hash', 64);
            $table->string('status', 32)->default('running')->index();
            $table->json('result')->nullable();
            $table->timestamps();

            $table->unique(
                ['bot_conversation_id', 'workflow_run_id', 'action_key', 'business_key'],
                'bot_action_executions_business_unique',
            );
            $table->index(['bot_id', 'action_key', 'created_at'], 'bot_action_executions_bot_action_created');
        });
    }

    public function down(): void
    {
        $this->schema()->dropIfExists('bot_action_executions');
    }
};
