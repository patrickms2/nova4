<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        $schema = $this->schema();

        $schema->table('agentic_bots', function (Blueprint $table) use ($schema): void {
            if (! $schema->hasColumn('agentic_bots', 'chat_provider_api_key')) {
                $table->text('chat_provider_api_key')->nullable()->after('model');
            }
        });

        $schema->table('bot_conversations', function (Blueprint $table) use ($schema): void {
            if (! $schema->hasColumn('bot_conversations', 'context_area')) {
                $table->string('context_area', 64)->nullable()->after('session_id');
            }
        });

        $defaultArea = trim((string) config('filament-agentic-chatbot.context.default_area', 'public'));
        if ($defaultArea === '') {
            $defaultArea = 'public';
        }

        $this->database()->table('bot_conversations')
            ->whereNull('context_area')
            ->update(['context_area' => mb_substr($defaultArea, 0, 64)]);

        if (! $schema->hasIndex('bot_conversations', 'bot_conversations_context_area_index')) {
            $schema->table('bot_conversations', function (Blueprint $table): void {
                $table->index('context_area', 'bot_conversations_context_area_index');
            });
        }

        if (! $schema->hasIndex('bot_conversations', 'bot_conversations_bot_session_area_index')) {
            $schema->table('bot_conversations', function (Blueprint $table): void {
                $table->index(
                    ['bot_id', 'session_id', 'context_area'],
                    'bot_conversations_bot_session_area_index'
                );
            });
        }
    }

    public function down(): void
    {
        $schema = $this->schema();

        if ($schema->hasIndex('bot_conversations', 'bot_conversations_bot_session_area_index')) {
            $schema->table('bot_conversations', function (Blueprint $table): void {
                $table->dropIndex('bot_conversations_bot_session_area_index');
            });
        }

        if ($schema->hasIndex('bot_conversations', 'bot_conversations_context_area_index')) {
            $schema->table('bot_conversations', function (Blueprint $table): void {
                $table->dropIndex('bot_conversations_context_area_index');
            });
        }

        $schema->table('bot_conversations', function (Blueprint $table) use ($schema): void {
            if ($schema->hasColumn('bot_conversations', 'context_area')) {
                $table->dropColumn('context_area');
            }
        });

        $schema->table('agentic_bots', function (Blueprint $table) use ($schema): void {
            if ($schema->hasColumn('agentic_bots', 'chat_provider_api_key')) {
                $table->dropColumn('chat_provider_api_key');
            }
        });
    }
};
