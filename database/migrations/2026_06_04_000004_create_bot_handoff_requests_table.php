<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        if ($this->schema()->hasTable('bot_handoff_requests')) {
            return;
        }

        $this->ensureTablesExist([
            'agentic_bots',
            'bot_conversations',
            'bot_messages',
            'workflow_runs',
            'agent_workflows',
        ], 'create bot_handoff_requests');

        $this->schema()->create('bot_handoff_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bot_id')
                ->constrained('agentic_bots')
                ->cascadeOnDelete();
            $table->foreignId('bot_conversation_id')
                ->nullable()
                ->constrained('bot_conversations')
                ->nullOnDelete();
            $table->foreignId('workflow_run_id')
                ->nullable()
                ->constrained('workflow_runs')
                ->nullOnDelete();
            $table->foreignId('agent_workflow_id')
                ->nullable()
                ->constrained('agent_workflows')
                ->nullOnDelete();
            $table->foreignId('trigger_message_id')
                ->nullable()
                ->constrained('bot_messages')
                ->nullOnDelete();

            $table->string('status', 32)->default('open');
            $table->string('priority', 32)->default('normal');
            $table->string('source', 64)->default('operator');
            $table->text('reason')->nullable();
            $table->text('summary')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('assigned_to_type')->nullable();
            $table->string('assigned_to_id', 128)->nullable();
            $table->string('assigned_to_label')->nullable();
            $table->json('payload')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['bot_id', 'status', 'priority'], 'bot_handoff_requests_bot_status_priority_index');
            $table->index(['bot_conversation_id', 'created_at'], 'bot_handoff_requests_conversation_created_index');
            $table->index(['agent_workflow_id', 'status'], 'bot_handoff_requests_workflow_status_index');
        });
    }

    public function down(): void
    {
        $this->schema()->dropIfExists('bot_handoff_requests');
    }
};
