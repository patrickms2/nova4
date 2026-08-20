<?php

use Heiner\FilamentAgenticChatbot\Support\EmbeddingDimensionResolver;
use Heiner\FilamentAgenticChatbot\Support\PackageMigration;

return new class extends PackageMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $schema = $this->schema();
        $backend = strtolower(trim((string) config('filament-agentic-chatbot.vector.backend', 'pgvector')));

        if ($backend !== 'pgvector') {
            return;
        }

        if ($this->database()->getDriverName() !== 'pgsql') {
            return;
        }

        if (! $schema->hasTable('bot_knowledge_chunks') || ! $schema->hasColumn('bot_knowledge_chunks', 'embedding')) {
            return;
        }

        $targetDimensions = EmbeddingDimensionResolver::resolve() ?? 3072;
        $currentDimensions = $this->currentEmbeddingDimensions();
        $hasChunks = $this->database()->table('bot_knowledge_chunks')->exists();

        if (
            $hasChunks
            && $currentDimensions !== null
            && $currentDimensions !== $targetDimensions
        ) {
            // Preserve existing vectors on upgrade and let operators plan re-ingestion explicitly.
            $this->database()->statement(
                "UPDATE bot_knowledge_sources
                SET meta = COALESCE(meta, '{}'::jsonb) || jsonb_build_object(
                    'warning', 'Vector dimension remains at ".$currentDimensions.' while configured target is '.$targetDimensions.". Re-ingest sources when changing embedding dimensions.',
                    'vector_dimension', ".$currentDimensions.'
                ),
                updated_at = NOW()'
            );

            $this->rebuildVectorIndex();

            return;
        }

        if ($currentDimensions !== null && $currentDimensions !== $targetDimensions) {
            // Drop the index first — IVFFlat indexes block ALTER COLUMN TYPE on large-dimension vectors.
            $this->database()->statement('DROP INDEX IF EXISTS bot_knowledge_chunks_embedding_cosine_idx');
            $this->database()->statement('ALTER TABLE bot_knowledge_chunks ALTER COLUMN embedding TYPE vector('.$targetDimensions.')');
        }

        $this->rebuildVectorIndex();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally no-op:
        // dimensional downgrades require controlled re-ingestion and are unsafe in generic rollback flows.
    }

    protected function currentEmbeddingDimensions(): ?int
    {
        try {
            $row = $this->database()->selectOne(
                "SELECT format_type(a.atttypid, a.atttypmod) AS column_type
                 FROM pg_attribute a
                 WHERE a.attrelid = 'bot_knowledge_chunks'::regclass
                   AND a.attname = 'embedding'
                   AND NOT a.attisdropped"
            );
        } catch (Throwable) {
            return null;
        }

        $columnType = strtolower((string) ($row->column_type ?? ''));
        $matches = [];

        if (preg_match('/^vector\((\d+)\)$/', $columnType, $matches) !== 1) {
            return null;
        }

        $resolvedDimension = (int) ($matches[1] ?? 0);

        return $resolvedDimension > 0 ? $resolvedDimension : null;
    }

    protected function rebuildVectorIndex(): void
    {
        $this->database()->statement('DROP INDEX IF EXISTS bot_knowledge_chunks_embedding_cosine_idx');

        $dimensions = $this->currentEmbeddingDimensions();

        if ($dimensions === null || $dimensions > 2000) {
            return;
        }

        $this->database()->statement(
            'CREATE INDEX bot_knowledge_chunks_embedding_cosine_idx
             ON bot_knowledge_chunks USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)'
        );
    }
};
