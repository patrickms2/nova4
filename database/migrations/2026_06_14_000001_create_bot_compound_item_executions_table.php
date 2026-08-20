<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        if ($this->schema()->hasTable('bot_compound_item_executions')) {
            return;
        }

        $this->ensureTablesExist([
            'agentic_bots',
            'bot_conversations',
            'bot_compound_requests',
        ], 'create bot_compound_item_executions');

        $this->schema()->create('bot_compound_item_executions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bot_id')->constrained('agentic_bots')->cascadeOnDelete();
            $table->foreignId('bot_conversation_id')->nullable()->constrained('bot_conversations')->nullOnDelete();
            $table->foreignId('bot_compound_request_id')->nullable()->constrained('bot_compound_requests')->cascadeOnDelete();
            $table->string('capability', 128);
            $table->string('item_key', 64);
            $table->string('input_hash', 64);
            $table->string('side_effect', 32)->default('write');
            $table->string('status', 32)->default('running')->index();
            $table->json('result')->nullable();
            $table->timestamps();

            $table->unique(
                ['bot_compound_request_id', 'capability', 'item_key'],
                'bot_compound_item_request_capability_unique',
            );
            $table->index(['bot_id', 'capability', 'created_at'], 'bot_compound_item_bot_capability_created');
        });
    }

    public function down(): void
    {
        $this->schema()->dropIfExists('bot_compound_item_executions');
    }
};
