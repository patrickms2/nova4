<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        if ($this->schema()->hasTable('workflow_runs')) {
            return;
        }

        $this->ensureTablesExist(['bot_conversations', 'agent_workflows'], 'create workflow_runs');

        $this->schema()->create('workflow_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_conversation_id')
                ->constrained('bot_conversations')
                ->cascadeOnDelete();
            $table->foreignId('agent_workflow_id')
                ->constrained('agent_workflows')
                ->cascadeOnDelete();

            // Status tracks the lifecycle of this run.
            $table->string('status', 32)->default('running')
                ->comment('running, halted, completed, failed, delayed, cancelled');
            $table->string('current_node_id')->nullable()
                ->comment('The node ID we are currently at or halted on.');
            $table->string('halt_reason')->nullable();

            // Persisted workflow state (variables, output, meta).
            $table->json('variables')->nullable();
            $table->text('output')->nullable();
            $table->json('meta')->nullable();
            $table->json('workflow_snapshot')->nullable()
                ->comment('Frozen workflow graph used for deterministic resumes.');

            // Execution tracking.
            $table->unsignedInteger('step_count')->default(0);
            $table->json('node_history')->nullable()
                ->comment('Ordered list of executed node IDs for debugging.');

            // Resume scheduling (for DelayExecutor).
            $table->timestamp('resume_at')->nullable();

            $table->timestamps();

            $table->index(['bot_conversation_id', 'status']);
        });
    }

    public function down(): void
    {
        $this->schema()->dropIfExists('workflow_runs');
    }
};
