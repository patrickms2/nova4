<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('bot_conversation_task_frames')) {
            return;
        }

        $this->deduplicateSourceRows();

        if ($schema->hasIndex('bot_conversation_task_frames', 'bot_task_frames_conversation_source_unique')) {
            return;
        }

        $schema->table('bot_conversation_task_frames', function (Blueprint $table): void {
            $table->unique(
                ['bot_conversation_id', 'source_type', 'source_id'],
                'bot_task_frames_conversation_source_unique',
            );
        });
    }

    public function down(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('bot_conversation_task_frames')) {
            return;
        }

        if (! $schema->hasIndex('bot_conversation_task_frames', 'bot_task_frames_conversation_source_unique')) {
            return;
        }

        $schema->table('bot_conversation_task_frames', function (Blueprint $table): void {
            $table->dropUnique('bot_task_frames_conversation_source_unique');
        });
    }

    private function deduplicateSourceRows(): void
    {
        $database = $this->database();

        $duplicates = $database->table('bot_conversation_task_frames')
            ->select(['bot_conversation_id', 'source_type', 'source_id'])
            ->selectRaw('MAX(id) as keep_id')
            ->whereNotNull('source_type')
            ->whereNotNull('source_id')
            ->groupBy('bot_conversation_id', 'source_type', 'source_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $database->table('bot_conversation_task_frames')
                ->where('bot_conversation_id', $duplicate->bot_conversation_id)
                ->where('source_type', $duplicate->source_type)
                ->where('source_id', $duplicate->source_id)
                ->where('id', '<>', $duplicate->keep_id)
                ->delete();
        }
    }
};
