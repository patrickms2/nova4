<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        if ($this->schema()->hasTable('workflow_memories')) {
            return;
        }

        $this->ensureTablesExist(['agentic_bots', 'bot_conversations', 'workflow_runs'], 'create workflow_memories');

        $this->schema()->create('workflow_memories', function (Blueprint $table): void {
            $table->id();
            $table->string('scope_type', 32);
            $table->string('scope_id', 160);
            $table->foreignId('bot_id')->nullable()->constrained('agentic_bots')->cascadeOnDelete();
            $table->foreignId('bot_conversation_id')->nullable()->constrained('bot_conversations')->cascadeOnDelete();
            $table->foreignId('workflow_run_id')->nullable()->constrained('workflow_runs')->cascadeOnDelete();
            $table->string('namespace', 96);
            $table->string('key', 191);
            $table->string('memory_type', 32)->default('state');
            $table->json('value')->nullable();
            $table->text('content')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['scope_type', 'scope_id', 'namespace', 'key'],
                'workflow_memories_scope_namespace_key_unique',
            );
            $table->index(['bot_conversation_id', 'namespace'], 'workflow_memories_conversation_namespace_index');
            $table->index(['workflow_run_id', 'namespace'], 'workflow_memories_run_namespace_index');
            $table->index(['bot_id', 'scope_type'], 'workflow_memories_bot_scope_index');
            $table->index(['memory_type', 'updated_at'], 'workflow_memories_type_updated_index');
            $table->index('expires_at', 'workflow_memories_expires_at_index');
        });
    }

    public function down(): void
    {
        $this->schema()->dropIfExists('workflow_memories');
    }
};
