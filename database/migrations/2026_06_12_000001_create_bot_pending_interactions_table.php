<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        $schema = $this->schema();

        if ($schema->hasTable('bot_pending_interactions')) {
            return;
        }

        $this->ensureTablesExist(['agentic_bots', 'bot_conversations', 'workflow_runs'], 'create bot_pending_interactions');

        $schema->create('bot_pending_interactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bot_id')->constrained('agentic_bots')->cascadeOnDelete();
            $table->foreignId('bot_conversation_id')->constrained('bot_conversations')->cascadeOnDelete();
            $table->foreignId('workflow_run_id')->nullable()->constrained('workflow_runs')->nullOnDelete();
            $table->foreignId('bot_message_id')->nullable()->constrained('bot_messages')->nullOnDelete();
            $table->string('source_type', 64);
            $table->string('source_id')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->string('kind', 64);
            $table->string('agent_graph_run_id')->nullable()->index();
            $table->string('agent_graph_thread_id')->nullable()->index();
            $table->string('agent_graph_interrupt_id');
            $table->string('agent_graph_checkpoint_id')->nullable();
            $table->string('node_id')->nullable();
            $table->string('interrupt_payload_hash', 64);
            $table->json('expects')->nullable();
            $table->json('context')->nullable();
            $table->json('resolution')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['bot_conversation_id', 'status', 'created_at'], 'bot_pending_conversation_status_created_index');
            $table->index(['workflow_run_id', 'status'], 'bot_pending_run_status_index');
            $table->unique(
                ['bot_conversation_id', 'agent_graph_interrupt_id', 'interrupt_payload_hash'],
                'bot_pending_conversation_interrupt_hash_unique',
            );
        });

        $this->createOnePendingPerConversationIndex();
    }

    public function down(): void
    {
        $this->schema()->dropIfExists('bot_pending_interactions');
    }

    private function createOnePendingPerConversationIndex(): void
    {
        $driver = $this->database()->getDriverName();

        if (! in_array($driver, ['pgsql', 'sqlite'], true)) {
            return;
        }

        $this->database()->statement(
            "CREATE UNIQUE INDEX IF NOT EXISTS bot_pending_one_pending_per_conversation_unique
             ON bot_pending_interactions (bot_conversation_id)
             WHERE status = 'pending'"
        );
    }
};
