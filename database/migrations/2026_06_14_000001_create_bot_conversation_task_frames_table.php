<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        if ($this->schema()->hasTable('bot_conversation_task_frames')) {
            return;
        }

        $this->ensureTablesExist([
            'agentic_bots',
            'bot_conversations',
            'agent_workflows',
        ], 'create bot_conversation_task_frames');

        $this->schema()->create('bot_conversation_task_frames', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bot_id')->constrained('agentic_bots')->cascadeOnDelete();
            $table->foreignId('bot_conversation_id')->constrained('bot_conversations')->cascadeOnDelete();
            $table->string('source_type', 64)->nullable();
            $table->string('source_id', 128)->nullable();
            $table->foreignId('agent_workflow_id')->nullable()->constrained('agent_workflows')->nullOnDelete();
            $table->string('capability_key', 128)->nullable()->index();
            $table->string('lane', 128)->nullable()->index();
            $table->string('intent', 128)->nullable()->index();
            $table->string('status', 40)->index();
            $table->string('side_effect', 40)->default('read')->index();
            $table->string('primary_slot', 128)->nullable();
            $table->json('frame');
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->index(['bot_conversation_id', 'updated_at'], 'bot_task_frames_conversation_updated_index');
            $table->index(['bot_conversation_id', 'status', 'updated_at'], 'bot_task_frames_conversation_status_index');
            $table->index(['bot_conversation_id', 'capability_key', 'updated_at'], 'bot_task_frames_conversation_capability_index');
            $table->index(['source_type', 'source_id'], 'bot_task_frames_source_index');
        });
    }

    public function down(): void
    {
        $this->schema()->dropIfExists('bot_conversation_task_frames');
    }
};
