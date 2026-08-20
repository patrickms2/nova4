<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        if ($this->schema()->hasTable('bot_submissions')) {
            return;
        }

        $this->ensureTablesExist(['agentic_bots', 'bot_conversations', 'workflow_runs', 'agent_workflows'], 'create bot_submissions');

        $this->schema()->create('bot_submissions', function (Blueprint $table) {
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

            $table->string('schema_key');
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->string('status', 32)->default('submitted');
            $table->string('dedupe_key')->nullable();
            $table->json('payload');
            $table->json('meta')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['bot_id', 'schema_key', 'status'], 'bot_submissions_bot_schema_status_index');
            $table->index(['bot_conversation_id', 'created_at'], 'bot_submissions_conversation_created_index');
            $table->unique(['bot_id', 'schema_key', 'dedupe_key'], 'bot_submissions_bot_schema_dedupe_unique');
        });
    }

    public function down(): void
    {
        $this->schema()->dropIfExists('bot_submissions');
    }
};
