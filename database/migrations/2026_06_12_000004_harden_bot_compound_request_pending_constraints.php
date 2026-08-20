<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;

return new class extends PackageMigration
{
    public function up(): void
    {
        if (! $this->schema()->hasTable('bot_compound_requests')) {
            return;
        }

        $this->deduplicatePendingRows();
        $this->createOnePendingPerConversationIndex();
    }

    public function down(): void
    {
        if (! $this->schema()->hasTable('bot_compound_requests')) {
            return;
        }

        $driver = $this->database()->getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            $this->database()->statement('DROP INDEX IF EXISTS bot_compound_one_pending_per_conversation_unique');
        }
    }

    private function deduplicatePendingRows(): void
    {
        $database = $this->database();

        $duplicates = $database->table('bot_compound_requests')
            ->select('bot_conversation_id')
            ->selectRaw('MAX(id) as keep_id')
            ->where('status', 'pending')
            ->groupBy('bot_conversation_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $database->table('bot_compound_requests')
                ->where('bot_conversation_id', $duplicate->bot_conversation_id)
                ->where('status', 'pending')
                ->where('id', '<>', $duplicate->keep_id)
                ->update([
                    'status' => 'superseded',
                    'updated_at' => now(),
                ]);
        }
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
