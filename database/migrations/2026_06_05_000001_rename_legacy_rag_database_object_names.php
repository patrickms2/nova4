<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;

return new class extends PackageMigration
{
    public function up(): void
    {
        if ($this->database()->getDriverName() !== 'pgsql') {
            return;
        }

        $this->renameConstraints($this->constraintRenames());
        $this->renameIndexes($this->indexRenames());
    }

    public function down(): void
    {
        if ($this->database()->getDriverName() !== 'pgsql') {
            return;
        }

        $this->renameConstraints($this->reverseRenames($this->constraintRenames()));
        $this->renameIndexes($this->reverseRenames($this->indexRenames()));
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function constraintRenames(): array
    {
        return [
            'agentic_bots' => [
                'rag_bots_pkey' => 'agentic_bots_pkey',
                'rag_bots_public_id_unique' => 'agentic_bots_public_id_unique',
            ],
            'bot_knowledge_sources' => [
                'rag_sources_pkey' => 'bot_knowledge_sources_pkey',
                'rag_sources_rag_bot_id_foreign' => 'bot_knowledge_sources_bot_id_foreign',
            ],
            'bot_knowledge_documents' => [
                'rag_documents_pkey' => 'bot_knowledge_documents_pkey',
                'rag_documents_rag_source_id_foreign' => 'bot_knowledge_documents_knowledge_source_id_foreign',
            ],
            'bot_knowledge_chunks' => [
                'rag_chunks_pkey' => 'bot_knowledge_chunks_pkey',
                'rag_chunks_rag_document_id_foreign' => 'bot_knowledge_chunks_knowledge_document_id_foreign',
            ],
            'bot_conversations' => [
                'rag_conversations_pkey' => 'bot_conversations_pkey',
                'rag_conversations_rag_bot_id_foreign' => 'bot_conversations_bot_id_foreign',
                'rag_conversations_bot_session_area_unique' => 'bot_conversations_bot_session_area_unique',
            ],
            'bot_messages' => [
                'rag_messages_pkey' => 'bot_messages_pkey',
                'rag_messages_rag_conversation_id_foreign' => 'bot_messages_bot_conversation_id_foreign',
            ],
            'agent_workflows' => [
                'agent_workflows_rag_bot_id_foreign' => 'agent_workflows_bot_id_foreign',
            ],
            'workflow_generation_runs' => [
                'workflow_generation_runs_rag_bot_id_foreign' => 'workflow_generation_runs_bot_id_foreign',
            ],
            'workflow_runs' => [
                'workflow_runs_rag_conversation_id_foreign' => 'workflow_runs_bot_conversation_id_foreign',
            ],
            'bot_access_tokens' => [
                'bot_access_tokens_rag_bot_id_foreign' => 'bot_access_tokens_bot_id_foreign',
            ],
            'bot_usage_events' => [
                'bot_usage_events_rag_bot_id_foreign' => 'bot_usage_events_bot_id_foreign',
                'bot_usage_events_rag_conversation_id_foreign' => 'bot_usage_events_bot_conversation_id_foreign',
            ],
            'channel_connections' => [
                'channel_connections_rag_bot_id_foreign' => 'channel_connections_bot_id_foreign',
            ],
            'channel_threads' => [
                'channel_threads_rag_bot_id_foreign' => 'channel_threads_bot_id_foreign',
                'channel_threads_rag_conversation_id_foreign' => 'channel_threads_bot_conversation_id_foreign',
            ],
            'workflow_memories' => [
                'workflow_memories_rag_bot_id_foreign' => 'workflow_memories_bot_id_foreign',
                'workflow_memories_rag_conversation_id_foreign' => 'workflow_memories_bot_conversation_id_foreign',
            ],
            'bot_submissions' => [
                'rag_submissions_pkey' => 'bot_submissions_pkey',
                'rag_submissions_rag_bot_id_foreign' => 'bot_submissions_bot_id_foreign',
                'rag_submissions_rag_conversation_id_foreign' => 'bot_submissions_bot_conversation_id_foreign',
                'rag_submissions_agent_workflow_id_foreign' => 'bot_submissions_agent_workflow_id_foreign',
                'rag_submissions_workflow_run_id_foreign' => 'bot_submissions_workflow_run_id_foreign',
                'rag_submissions_bot_schema_dedupe_unique' => 'bot_submissions_bot_schema_dedupe_unique',
            ],
            'bot_submission_audits' => [
                'rag_submission_audits_pkey' => 'bot_submission_audits_pkey',
                'rag_submission_audits_rag_submission_id_foreign' => 'bot_submission_audits_bot_submission_id_foreign',
                'rag_submission_audits_rag_bot_id_foreign' => 'bot_submission_audits_bot_id_foreign',
                'rag_submission_audits_rag_conversation_id_foreign' => 'bot_submission_audits_bot_conversation_id_foreign',
                'rag_submission_audits_agent_workflow_id_foreign' => 'bot_submission_audits_agent_workflow_id_foreign',
                'rag_submission_audits_workflow_run_id_foreign' => 'bot_submission_audits_workflow_run_id_foreign',
            ],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function indexRenames(): array
    {
        return [
            'agentic_bots' => [
                'rag_bots_is_active_index' => 'agentic_bots_is_active_index',
                'rag_bots_pkey' => 'agentic_bots_pkey',
                'rag_bots_public_id_unique' => 'agentic_bots_public_id_unique',
            ],
            'bot_knowledge_sources' => [
                'rag_sources_pkey' => 'bot_knowledge_sources_pkey',
            ],
            'bot_knowledge_documents' => [
                'rag_documents_content_hash_index' => 'bot_knowledge_documents_content_hash_index',
                'rag_documents_pkey' => 'bot_knowledge_documents_pkey',
            ],
            'bot_knowledge_chunks' => [
                'rag_chunks_embedding_cosine_idx' => 'bot_knowledge_chunks_embedding_cosine_idx',
                'rag_chunks_pkey' => 'bot_knowledge_chunks_pkey',
                'rag_chunks_rag_document_id_chunk_index_index' => 'bot_knowledge_chunks_knowledge_document_id_chunk_index_index',
            ],
            'bot_conversations' => [
                'rag_conversations_bot_session_area_unique' => 'bot_conversations_bot_session_area_unique',
                'rag_conversations_context_area_index' => 'bot_conversations_context_area_index',
                'rag_conversations_pkey' => 'bot_conversations_pkey',
                'rag_conversations_rag_bot_id_session_id_index' => 'bot_conversations_bot_id_session_id_index',
                'rag_conversations_session_id_index' => 'bot_conversations_session_id_index',
            ],
            'bot_messages' => [
                'rag_messages_pkey' => 'bot_messages_pkey',
            ],
            'bot_access_tokens' => [
                'bot_access_tokens_rag_bot_id_is_active_index' => 'bot_access_tokens_bot_id_is_active_index',
            ],
            'bot_usage_events' => [
                'bot_usage_events_rag_bot_id_occurred_at_index' => 'bot_usage_events_bot_id_occurred_at_index',
            ],
            'channel_connections' => [
                'channel_connections_rag_bot_id_channel_index' => 'channel_connections_bot_id_channel_index',
                'channel_connections_rag_bot_id_is_active_index' => 'channel_connections_bot_id_is_active_index',
            ],
            'workflow_runs' => [
                'workflow_runs_rag_conversation_id_status_index' => 'workflow_runs_bot_conversation_id_status_index',
            ],
            'bot_submissions' => [
                'rag_submissions_bot_schema_dedupe_unique' => 'bot_submissions_bot_schema_dedupe_unique',
                'rag_submissions_bot_schema_status_index' => 'bot_submissions_bot_schema_status_index',
                'rag_submissions_conversation_created_index' => 'bot_submissions_conversation_created_index',
                'rag_submissions_pkey' => 'bot_submissions_pkey',
            ],
            'bot_submission_audits' => [
                'rag_submission_audits_bot_event_index' => 'bot_submission_audits_bot_event_index',
                'rag_submission_audits_pkey' => 'bot_submission_audits_pkey',
                'rag_submission_audits_run_created_index' => 'bot_submission_audits_run_created_index',
                'rag_submission_audits_submission_created_index' => 'bot_submission_audits_submission_created_index',
            ],
        ];
    }

    /**
     * @param  array<string, array<string, string>>  $renames
     */
    private function renameConstraints(array $renames): void
    {
        foreach ($renames as $table => $tableRenames) {
            if (! $this->schema()->hasTable($table)) {
                continue;
            }

            foreach ($tableRenames as $oldName => $newName) {
                $this->renameConstraintIfNeeded($table, $oldName, $newName);
            }
        }
    }

    /**
     * @param  array<string, array<string, string>>  $renames
     */
    private function renameIndexes(array $renames): void
    {
        foreach ($renames as $table => $tableRenames) {
            if (! $this->schema()->hasTable($table)) {
                continue;
            }

            foreach ($tableRenames as $oldName => $newName) {
                $this->renameIndexIfNeeded($table, $oldName, $newName);
            }
        }
    }

    private function renameConstraintIfNeeded(string $table, string $oldName, string $newName): void
    {
        if (! $this->constraintExists($table, $oldName) || $this->constraintExists($table, $newName)) {
            return;
        }

        $this->database()->statement(
            'ALTER TABLE '.$this->quoteIdentifier($table)
            .' RENAME CONSTRAINT '.$this->quoteIdentifier($oldName)
            .' TO '.$this->quoteIdentifier($newName)
        );
    }

    private function renameIndexIfNeeded(string $table, string $oldName, string $newName): void
    {
        if (! $this->indexExists($table, $oldName) || $this->indexExists($table, $newName)) {
            return;
        }

        $this->database()->statement(
            'ALTER INDEX '.$this->quoteIdentifier($oldName).' RENAME TO '.$this->quoteIdentifier($newName)
        );
    }

    private function constraintExists(string $table, string $constraintName): bool
    {
        $row = $this->database()->selectOne(
            'SELECT 1
             FROM pg_constraint c
             JOIN pg_class t ON t.oid = c.conrelid
             JOIN pg_namespace n ON n.oid = t.relnamespace
             WHERE n.nspname = ANY (current_schemas(false))
               AND t.relname = ?
               AND c.conname = ?
             LIMIT 1',
            [$table, $constraintName]
        );

        return $row !== null;
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $row = $this->database()->selectOne(
            'SELECT 1
             FROM pg_indexes
             WHERE schemaname = ANY (current_schemas(false))
               AND tablename = ?
               AND indexname = ?
             LIMIT 1',
            [$table, $indexName]
        );

        return $row !== null;
    }

    /**
     * @param  array<string, array<string, string>>  $renames
     * @return array<string, array<string, string>>
     */
    private function reverseRenames(array $renames): array
    {
        $reversed = [];

        foreach ($renames as $table => $tableRenames) {
            $reversed[$table] = array_flip($tableRenames);
        }

        return $reversed;
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }
};
