<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;

return new class extends PackageMigration
{
    public function up(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('bot_pending_interactions')) {
            return;
        }

        $this->backfillInterruptIds();
        $this->deduplicatePendingRows();
        $this->hardenInterruptIdNullability();
        $this->createOnePendingPerConversationIndex();
    }

    public function down(): void
    {
        if (! $this->schema()->hasTable('bot_pending_interactions')) {
            return;
        }

        $driver = $this->database()->getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            $this->database()->statement('DROP INDEX IF EXISTS bot_pending_one_pending_per_conversation_unique');
        }
    }

    private function backfillInterruptIds(): void
    {
        $database = $this->database();

        $database->table('bot_pending_interactions')
            ->whereNull('agent_graph_interrupt_id')
            ->orWhere('agent_graph_interrupt_id', '')
            ->orderBy('id')
            ->select(['id'])
            ->chunkById(100, function ($rows) use ($database): void {
                foreach ($rows as $row) {
                    $database->table('bot_pending_interactions')
                        ->where('id', $row->id)
                        ->update([
                            'agent_graph_interrupt_id' => 'synthetic:legacy:'.$row->id,
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    private function deduplicatePendingRows(): void
    {
        $database = $this->database();

        $duplicates = $database->table('bot_pending_interactions')
            ->select('bot_conversation_id')
            ->selectRaw('MAX(id) as keep_id')
            ->where('status', 'pending')
            ->groupBy('bot_conversation_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $database->table('bot_pending_interactions')
                ->where('bot_conversation_id', $duplicate->bot_conversation_id)
                ->where('status', 'pending')
                ->where('id', '<>', $duplicate->keep_id)
                ->update([
                    'status' => 'superseded',
                    'updated_at' => now(),
                ]);
        }
    }

    private function hardenInterruptIdNullability(): void
    {
        $driver = $this->database()->getDriverName();

        if ($driver === 'pgsql') {
            $this->database()->statement(
                'ALTER TABLE "bot_pending_interactions" ALTER COLUMN "agent_graph_interrupt_id" SET NOT NULL'
            );

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->database()->statement(
                'ALTER TABLE bot_pending_interactions MODIFY agent_graph_interrupt_id VARCHAR(255) NOT NULL'
            );
        }
    }

    private function createOnePendingPerConversationIndex(): void
    {
        $driver = $this->database()->getDriverName();

        if (! in_array($driver, ['pgsql', 'sqlite'], true)) {
            return;
        }

        $this->database()->statement(
            "CREATE UNIQUE INDEX IF NOT EXISTS bot_pending_one_pending_per_conversation_unique
             ON bot_pending_interactions (bot_conversation_id)
             WHERE status = 'pending'"
        );
    }
};
