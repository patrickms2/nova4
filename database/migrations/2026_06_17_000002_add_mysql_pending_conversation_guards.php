<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;

return new class extends PackageMigration
{
    public function up(): void
    {
        if (! $this->usesMysqlFamily()) {
            return;
        }

        $this->hardenPendingInteractions();
        $this->hardenCompoundRequests();
    }

    public function down(): void
    {
        if (! $this->usesMysqlFamily()) {
            return;
        }

        $this->dropGuard('bot_pending_interactions', 'pending_conversation_guard', 'bot_pending_mysql_one_pending_unique');
        $this->dropGuard('bot_compound_requests', 'pending_conversation_guard', 'bot_compound_mysql_one_pending_unique');
    }

    private function hardenPendingInteractions(): void
    {
        if (! $this->schema()->hasTable('bot_pending_interactions')) {
            return;
        }

        $this->deduplicatePendingRows('bot_pending_interactions');
        $this->addGuard(
            table: 'bot_pending_interactions',
            column: 'pending_conversation_guard',
            index: 'bot_pending_mysql_one_pending_unique',
        );
    }

    private function hardenCompoundRequests(): void
    {
        if (! $this->schema()->hasTable('bot_compound_requests')) {
            return;
        }

        $this->deduplicatePendingRows('bot_compound_requests');
        $this->addGuard(
            table: 'bot_compound_requests',
            column: 'pending_conversation_guard',
            index: 'bot_compound_mysql_one_pending_unique',
        );
    }

    private function usesMysqlFamily(): bool
    {
        return in_array($this->database()->getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function deduplicatePendingRows(string $table): void
    {
        $database = $this->database();

        $duplicates = $database->table($table)
            ->select('bot_conversation_id')
            ->selectRaw('MAX(id) as keep_id')
            ->where('status', 'pending')
            ->groupBy('bot_conversation_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $database->table($table)
                ->where('bot_conversation_id', $duplicate->bot_conversation_id)
                ->where('status', 'pending')
                ->where('id', '<>', $duplicate->keep_id)
                ->update([
                    'status' => 'superseded',
                    'updated_at' => now(),
                ]);
        }
    }

    private function addGuard(string $table, string $column, string $index): void
    {
        $schema = $this->schema();
        if (! $schema->hasColumn($table, $column)) {
            $this->database()->statement(
                "ALTER TABLE `{$table}`
                 ADD COLUMN `{$column}` BIGINT UNSIGNED
                 GENERATED ALWAYS AS (
                     CASE WHEN `status` = 'pending' AND `bot_conversation_id` IS NOT NULL THEN `bot_conversation_id` ELSE NULL END
                 ) "
            );
        }

        if (! $schema->hasIndex($table, $index)) {
            $this->database()->statement("CREATE UNIQUE INDEX `{$index}` ON `{$table}` (`{$column}`)");
        }
    }

    private function dropGuard(string $table, string $column, string $index): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable($table)) {
            return;
        }

        if ($schema->hasIndex($table, $index)) {
            $this->database()->statement("DROP INDEX `{$index}` ON `{$table}`");
        }

        if ($schema->hasColumn($table, $column)) {
            $this->database()->statement("ALTER TABLE `{$table}` DROP COLUMN `{$column}`");
        }
    }
};
