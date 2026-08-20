<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        $schema = $this->schema();

        $this->ensureTablesExist(['agentic_bots', 'bot_access_tokens'], 'create channel integration tables');

        if (! $schema->hasTable('channel_connections')) {
            $schema->create('channel_connections', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('bot_id')->constrained('agentic_bots')->cascadeOnDelete();
                $table->foreignId('bot_access_token_id')->nullable()->constrained('bot_access_tokens')->nullOnDelete();
                $table->string('public_id', 64)->unique();
                $table->string('webhook_key', 96)->unique();
                $table->string('name');
                $table->string('channel', 32)->index();
                $table->string('provider', 64)->index();
                $table->string('default_area', 64)->default('public');
                $table->text('credentials')->nullable();
                $table->json('settings')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamp('last_webhook_at')->nullable();
                $table->timestamp('last_outbound_at')->nullable();
                $table->text('last_error')->nullable();
                $table->timestamps();
                $table->index(['bot_id', 'channel']);
                $table->index(['bot_id', 'is_active']);
            });
        }

        if (! $schema->hasTable('channel_threads')) {
            $schema->create('channel_threads', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('channel_connection_id')->constrained('channel_connections')->cascadeOnDelete();
                $table->foreignId('bot_id')->constrained('agentic_bots')->cascadeOnDelete();
                $table->foreignId('bot_conversation_id')->nullable()->constrained('bot_conversations')->nullOnDelete();
                $table->string('session_id', 128)->index();
                $table->string('context_area', 64)->default('public');
                $table->string('external_thread_id', 255);
                $table->string('external_user_id', 255)->nullable();
                $table->string('external_user_label')->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('last_inbound_at')->nullable();
                $table->timestamp('last_outbound_at')->nullable();
                $table->timestamps();
                $table->unique(['channel_connection_id', 'external_thread_id'], 'channel_threads_connection_thread_unique');
                $table->index(['bot_id', 'session_id', 'context_area'], 'channel_threads_bot_session_area_index');
            });
        }

        if (! $schema->hasTable('channel_delivery_events')) {
            $schema->create('channel_delivery_events', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('channel_connection_id')->constrained('channel_connections')->cascadeOnDelete();
                $table->foreignId('channel_thread_id')->nullable()->constrained('channel_threads')->nullOnDelete();
                $table->string('direction', 16);
                $table->string('status', 32)->default('received')->index();
                $table->string('external_event_id', 255)->nullable();
                $table->text('message_preview')->nullable();
                $table->json('payload')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('occurred_at')->nullable();
                $table->timestamps();
                $table->unique(['channel_connection_id', 'direction', 'external_event_id'], 'channel_events_connection_direction_event_unique');
                $table->index(['channel_connection_id', 'created_at'], 'channel_events_connection_created_index');
            });
        }
    }

    public function down(): void
    {
        $schema = $this->schema();

        $schema->dropIfExists('channel_delivery_events');
        $schema->dropIfExists('channel_threads');
        $schema->dropIfExists('channel_connections');
    }
};
