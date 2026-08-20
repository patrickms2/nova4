<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        $schema = $this->schema();

        if ($schema->hasTable('bot_compound_requests')) {
            return;
        }

        $schema->create('bot_compound_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bot_id')->constrained('agentic_bots')->cascadeOnDelete();
            $table->foreignId('bot_conversation_id')->constrained('bot_conversations')->cascadeOnDelete();
            $table->foreignId('trigger_message_id')->nullable()->constrained('bot_messages')->nullOnDelete();
            $table->string('status', 32)->default('pending')->index();
            $table->json('plan');
            $table->json('result')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
            $table->index(['bot_conversation_id', 'status', 'expires_at'], 'bot_compound_conversation_status_expiry_index');
            $table->index(['bot_id', 'status', 'created_at'], 'bot_compound_bot_status_created_index');
        });

        $this->createOnePendingPerConversationIndex();
    }

    public function down(): void
    {
        $this->schema()->dropIfExists('bot_compound_requests');
    }

    private function createOnePendingPerConversationIndex(): void
    {
        $driver = $this->database()->getDriverName();

        if (! in_array($driver, ['pgsql', 'sqlite'], true)) {
            return;
        }

        $this->database()->statement(
            "CREATE UNIQUE INDEX IF NOT EXISTS bot_compound_one_pending_per_conversation_unique
             ON bot_compound_requests (bot_conversation_id)
             WHERE status = 'pending'"
        );
    }
};
