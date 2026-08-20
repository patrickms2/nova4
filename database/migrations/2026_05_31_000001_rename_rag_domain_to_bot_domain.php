<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tableRenames() as $old => $new) {
            $this->renameTableIfNeeded($old, $new);
        }

        foreach ($this->columnRenames() as $table => $columns) {
            foreach ($columns as $old => $new) {
                $this->renameColumnIfNeeded($table, $old, $new);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->columnRenames() as $table => $columns) {
            foreach ($columns as $old => $new) {
                $this->renameColumnIfNeeded($table, $new, $old);
            }
        }

        foreach (array_reverse($this->tableRenames(), true) as $old => $new) {
            $this->renameTableIfNeeded($new, $old);
        }
    }

    /**
     * @return array<string, string>
     */
    private function tableRenames(): array
    {
        return [
            'rag_bots' => 'agentic_bots',
            'rag_sources' => 'bot_knowledge_sources',
            'rag_documents' => 'bot_knowledge_documents',
            'rag_chunks' => 'bot_knowledge_chunks',
            'rag_conversations' => 'bot_conversations',
            'rag_messages' => 'bot_messages',
            'rag_submissions' => 'bot_submissions',
            'rag_submission_audits' => 'bot_submission_audits',
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function columnRenames(): array
    {
        return [
            'agentic_bots' => [
                'rag_config' => 'runtime_config',
            ],
            'bot_knowledge_sources' => [
                'rag_bot_id' => 'bot_id',
            ],
            'bot_knowledge_documents' => [
                'rag_source_id' => 'knowledge_source_id',
            ],
            'bot_knowledge_chunks' => [
                'rag_document_id' => 'knowledge_document_id',
            ],
            'bot_conversations' => [
                'rag_bot_id' => 'bot_id',
            ],
            'bot_messages' => [
                'rag_conversation_id' => 'bot_conversation_id',
            ],
            'bot_submissions' => [
                'rag_bot_id' => 'bot_id',
                'rag_conversation_id' => 'bot_conversation_id',
            ],
            'bot_submission_audits' => [
                'rag_submission_id' => 'bot_submission_id',
                'rag_bot_id' => 'bot_id',
                'rag_conversation_id' => 'bot_conversation_id',
            ],
            'agent_workflows' => [
                'rag_bot_id' => 'bot_id',
            ],
            'workflow_generation_runs' => [
                'rag_bot_id' => 'bot_id',
            ],
            'bot_access_tokens' => [
                'rag_bot_id' => 'bot_id',
            ],
            'bot_usage_events' => [
                'rag_bot_id' => 'bot_id',
                'rag_conversation_id' => 'bot_conversation_id',
            ],
            'channel_connections' => [
                'rag_bot_id' => 'bot_id',
            ],
            'channel_threads' => [
                'rag_bot_id' => 'bot_id',
                'rag_conversation_id' => 'bot_conversation_id',
            ],
            'workflow_runs' => [
                'rag_conversation_id' => 'bot_conversation_id',
            ],
            'workflow_memories' => [
                'rag_bot_id' => 'bot_id',
                'rag_conversation_id' => 'bot_conversation_id',
            ],
        ];
    }

    private function renameTableIfNeeded(string $old, string $new): void
    {
        $schema = $this->schema();

        if ($schema->hasTable($new) || ! $schema->hasTable($old)) {
            return;
        }

        $schema->rename($old, $new);
    }

    private function renameColumnIfNeeded(string $table, string $old, string $new): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable($table)) {
            return;
        }

        if (! $schema->hasColumn($table, $old) || $schema->hasColumn($table, $new)) {
            return;
        }

        $schema->table($table, function (Blueprint $table) use ($old, $new): void {
            $table->renameColumn($old, $new);
        });
    }
};
