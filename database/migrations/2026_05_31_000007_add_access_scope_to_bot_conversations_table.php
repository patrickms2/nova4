<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        $schema = $this->schema();

        $this->ensureTablesExist(['bot_conversations', 'bot_access_tokens'], 'add bot conversation access scope');

        $schema->table('bot_conversations', function (Blueprint $table) use ($schema): void {
            if (! $schema->hasColumn('bot_conversations', 'bot_access_token_id')) {
                $table->foreignId('bot_access_token_id')
                    ->nullable()
                    ->after('context_area')
                    ->constrained('bot_access_tokens')
                    ->nullOnDelete();
            }

            if (! $schema->hasColumn('bot_conversations', 'owner_type')) {
                $table->string('owner_type')->nullable()->after('bot_access_token_id');
            }

            if (! $schema->hasColumn('bot_conversations', 'owner_id')) {
                $table->string('owner_id', 128)->nullable()->after('owner_type');
            }

            if (! $schema->hasColumn('bot_conversations', 'channel')) {
                $table->string('channel', 64)->nullable()->after('owner_id');
            }

            if (! $schema->hasColumn('bot_conversations', 'channel_connection_id')) {
                $table->foreignId('channel_connection_id')
                    ->nullable()
                    ->after('channel')
                    ->constrained('channel_connections')
                    ->nullOnDelete();
            }
        });

        $schema->table('bot_conversations', function (Blueprint $table) use ($schema): void {
            if (! $schema->hasIndex('bot_conversations', 'bot_conversations_token_area_index')) {
                $table->index(['bot_access_token_id', 'context_area'], 'bot_conversations_token_area_index');
            }

            if (! $schema->hasIndex('bot_conversations', 'bot_conversations_owner_index')) {
                $table->index(['owner_type', 'owner_id'], 'bot_conversations_owner_index');
            }

            if (! $schema->hasIndex('bot_conversations', 'bot_conversations_channel_area_index')) {
                $table->index(['channel', 'context_area'], 'bot_conversations_channel_area_index');
            }
        });
    }

    public function down(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('bot_conversations')) {
            return;
        }

        $schema->table('bot_conversations', function (Blueprint $table) use ($schema): void {
            if ($schema->hasIndex('bot_conversations', 'bot_conversations_channel_area_index')) {
                $table->dropIndex('bot_conversations_channel_area_index');
            }

            if ($schema->hasIndex('bot_conversations', 'bot_conversations_owner_index')) {
                $table->dropIndex('bot_conversations_owner_index');
            }

            if ($schema->hasIndex('bot_conversations', 'bot_conversations_token_area_index')) {
                $table->dropIndex('bot_conversations_token_area_index');
            }
        });

        $schema->table('bot_conversations', function (Blueprint $table) use ($schema): void {
            if ($schema->hasColumn('bot_conversations', 'channel_connection_id')) {
                $table->dropConstrainedForeignId('channel_connection_id');
            }

            foreach (['channel', 'owner_id', 'owner_type'] as $column) {
                if ($schema->hasColumn('bot_conversations', $column)) {
                    $table->dropColumn($column);
                }
            }

            if ($schema->hasColumn('bot_conversations', 'bot_access_token_id')) {
                $table->dropConstrainedForeignId('bot_access_token_id');
            }
        });
    }
};
