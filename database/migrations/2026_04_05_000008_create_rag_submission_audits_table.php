<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        if ($this->schema()->hasTable('bot_submission_audits')) {
            return;
        }

        $this->ensureTablesExist(['bot_submissions', 'agentic_bots', 'bot_conversations', 'workflow_runs', 'agent_workflows'], 'create bot_submission_audits');

        $this->schema()->create('bot_submission_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_submission_id')
                ->constrained('bot_submissions')
                ->cascadeOnDelete();
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
            $table->string('event_type', 64);
            $table->string('actor_type')->nullable();
            $table->string('actor_id', 128)->nullable();
            $table->string('actor_label')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['bot_submission_id', 'created_at'], 'bot_submission_audits_submission_created_index');
            $table->index(['bot_id', 'event_type'], 'bot_submission_audits_bot_event_index');
            $table->index(['workflow_run_id', 'created_at'], 'bot_submission_audits_run_created_index');
        });
    }

    public function down(): void
    {
        $this->schema()->dropIfExists('bot_submission_audits');
    }
};
