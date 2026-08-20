<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;

return new class extends PackageMigration
{
    public function up(): void
    {
        if ($this->database()->getDriverName() !== 'pgsql') {
            return;
        }

        if (! $this->schema()->hasTable('bot_knowledge_chunks')) {
            return;
        }

        $this->renameIndexIfNeeded(
            'rag_chunks_embedding_cosine_idx',
            'bot_knowledge_chunks_embedding_cosine_idx'
        );
    }

    public function down(): void
    {
        if ($this->database()->getDriverName() !== 'pgsql') {
            return;
        }

        if (! $this->schema()->hasTable('bot_knowledge_chunks')) {
            return;
        }

        $this->renameIndexIfNeeded(
            'bot_knowledge_chunks_embedding_cosine_idx',
            'rag_chunks_embedding_cosine_idx'
        );
    }

    private function renameIndexIfNeeded(string $oldName, string $newName): void
    {
        if (! $this->indexExists($oldName) || $this->indexExists($newName)) {
            return;
        }

        $this->database()->statement(
            'ALTER INDEX '.$this->quoteIdentifier($oldName).' RENAME TO '.$this->quoteIdentifier($newName)
        );
    }

    private function indexExists(string $indexName): bool
    {
        $row = $this->database()->selectOne(
            "SELECT 1
             FROM pg_indexes
             WHERE schemaname = ANY (current_schemas(false))
               AND tablename = 'bot_knowledge_chunks'
               AND indexname = ?
             LIMIT 1",
            [$indexName]
        );

        return $row !== null;
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }
};
