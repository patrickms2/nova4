<?php

use Heiner\FilamentAgenticChatbot\Support\EmbeddingDimensionResolver;
use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    protected int $vectorDimensions;

    public function __construct()
    {
        parent::__construct();
        $this->vectorDimensions = EmbeddingDimensionResolver::resolve() ?? 3072;
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $schema = $this->schema();
        $usePgVectorColumn = $this->shouldUsePgVectorColumn();

        if (! $schema->hasTable('agentic_bots')) {
            $schema->create('agentic_bots', function (Blueprint $table) {
                $table->id();
                $table->string('public_id', 36)->unique();
                $table->string('name');
                $table->text('system_prompt')->nullable();
                $table->string('model')->default('gemma-3-1b-it'); // sensible low-cost default
                $table->json('runtime_config')->nullable(); // top_k, min_similarity
                $table->json('allowed_domains')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('bot_knowledge_sources')) {
            $schema->create('bot_knowledge_sources', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bot_id')->constrained('agentic_bots')->cascadeOnDelete();
                $table->string('name');
                $table->string('type'); // file, url, text
                $table->string('status')->default('pending'); // pending, processing, completed, failed
                $table->json('meta')->nullable(); // path, url, etc.
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('bot_knowledge_documents')) {
            $schema->create('bot_knowledge_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('knowledge_source_id')->constrained('bot_knowledge_sources')->cascadeOnDelete();
                $table->string('content_hash')->index();
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('bot_knowledge_chunks')) {
            $schema->create('bot_knowledge_chunks', function (Blueprint $table) use ($usePgVectorColumn) {
                $table->id();
                $table->foreignId('knowledge_document_id')->constrained('bot_knowledge_documents')->cascadeOnDelete();
                $table->text('content');
                $table->integer('chunk_index');
                $table->integer('token_count')->nullable();
                $table->json('meta')->nullable();
                $table->index(['knowledge_document_id', 'chunk_index']);
                if (! $usePgVectorColumn) {
                    $table->longText('embedding')->nullable();
                }
                // Use raw SQL for the vector column to support pgvector.
            });
        } elseif (! $schema->hasColumn('bot_knowledge_chunks', 'meta')) {
            $schema->table('bot_knowledge_chunks', function (Blueprint $table): void {
                $table->json('meta')->nullable()->after('token_count');
            });
        }

        if ($usePgVectorColumn) {
            // Add vector column separately since Blueprint doesn't support it natively without specific drivers loaded during migration time sometimes.
            if (! $schema->hasColumn('bot_knowledge_chunks', 'embedding')) {
                $this->database()->statement('ALTER TABLE bot_knowledge_chunks ADD COLUMN embedding vector('.$this->vectorDimensions.')');
            }

            // pgvector ANN indexes have dimension limits (ivfflat/hnsw vector opclasses commonly <= 2000 dims).
            // Skip ANN index creation for higher dimensions so fresh installs do not fail.
            if ($this->canCreateAnnVectorIndex()) {
                $this->database()->statement(
                    'CREATE INDEX IF NOT EXISTS bot_knowledge_chunks_embedding_cosine_idx ON bot_knowledge_chunks USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)'
                );
            }
        }

        if (! $schema->hasTable('bot_conversations')) {
            $schema->create('bot_conversations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bot_id')->constrained('agentic_bots')->cascadeOnDelete();
                $table->string('session_id')->index(); // or user_id
                $table->timestamps();
                $table->index(['bot_id', 'session_id']);
            });
        }

        if (! $schema->hasTable('bot_messages')) {
            $schema->create('bot_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bot_conversation_id')->constrained('bot_conversations')->cascadeOnDelete();
                $table->string('role'); // user, assistant
                $table->text('content');
                $table->json('sources')->nullable(); // cited chunks
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $schema = $this->schema();

        $schema->dropIfExists('bot_messages');
        $schema->dropIfExists('bot_conversations');
        $schema->dropIfExists('bot_knowledge_chunks');
        $schema->dropIfExists('bot_knowledge_documents');
        $schema->dropIfExists('bot_knowledge_sources');
        $schema->dropIfExists('agentic_bots');
    }

    protected function shouldUsePgVectorColumn(): bool
    {
        $backend = strtolower(trim((string) config('filament-agentic-chatbot.vector.backend', 'pgvector')));

        if ($backend !== 'pgvector') {
            return false;
        }

        return $this->database()->getDriverName() === 'pgsql';
    }

    protected function canCreateAnnVectorIndex(): bool
    {
        return $this->vectorDimensions > 0 && $this->vectorDimensions <= 2000;
    }
};
