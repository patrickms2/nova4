<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        $this->promoteSystemPromptToText();
        $this->enforceConversationUniqueness();
    }

    public function down(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('bot_conversations')) {
            return;
        }

        if ($schema->hasIndex('bot_conversations', 'bot_conversations_bot_session_area_unique')) {
            $schema->table('bot_conversations', function (Blueprint $table): void {
                $table->dropUnique('bot_conversations_bot_session_area_unique');
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

    protected function promoteSystemPromptToText(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('agentic_bots') || ! $schema->hasColumn('agentic_bots', 'system_prompt')) {
            return;
        }

        $driver = $this->database()->getDriverName();

        if ($driver === 'pgsql') {
            $this->database()->statement('ALTER TABLE agentic_bots ALTER COLUMN system_prompt TYPE text');

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->database()->statement('ALTER TABLE agentic_bots MODIFY system_prompt TEXT NULL');
        }
    }

    protected function enforceConversationUniqueness(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('bot_conversations')) {
            return;
        }

        $defaultArea = trim((string) config('filament-agentic-chatbot.context.default_area', 'public'));

        if ($defaultArea === '') {
            $defaultArea = 'public';
        }

        $defaultArea = mb_substr(strtolower($defaultArea), 0, 64);

        $this->database()->table('bot_conversations')
            ->whereNull('context_area')
            ->orWhere('context_area', '')
            ->update(['context_area' => $defaultArea]);

        $duplicates = $this->database()->table('bot_conversations')
            ->select('bot_id', 'session_id', 'context_area', $this->database()->raw('COUNT(*) as duplicate_count'))
            ->groupBy('bot_id', 'session_id', 'context_area')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $conversationIds = $this->database()->table('bot_conversations')
                ->where('bot_id', $duplicate->bot_id)
                ->where('session_id', $duplicate->session_id)
                ->where('context_area', $duplicate->context_area)
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            if (count($conversationIds) <= 1) {
                continue;
            }

            $keptConversationId = array_shift($conversationIds);

            $this->database()->table('bot_messages')
                ->whereIn('bot_conversation_id', $conversationIds)
                ->update(['bot_conversation_id' => $keptConversationId]);

            $this->database()->table('bot_conversations')
                ->whereIn('id', $conversationIds)
                ->delete();
        }

        if ($schema->hasIndex('bot_conversations', 'bot_conversations_bot_session_area_index')) {
            $schema->table('bot_conversations', function (Blueprint $table): void {
                $table->dropIndex('bot_conversations_bot_session_area_index');
            });
        }

        if (! $schema->hasIndex('bot_conversations', 'bot_conversations_bot_session_area_unique')) {
            $schema->table('bot_conversations', function (Blueprint $table): void {
                $table->unique(
                    ['bot_id', 'session_id', 'context_area'],
                    'bot_conversations_bot_session_area_unique'
                );
            });
        }
    }
};
