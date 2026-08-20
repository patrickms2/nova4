<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;

return new class extends PackageMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->jsonColumns() as $table => $columns) {
            $this->replaceLegacyVariables($table, $columns, true);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->jsonColumns() as $table => $columns) {
            $this->replaceLegacyVariables($table, $columns, false);
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function jsonColumns(): array
    {
        return [
            'agent_workflows' => ['workflow_data', 'draft_workflow_data'],
            'agent_workflow_versions' => ['workflow_data'],
            'workflow_runs' => ['variables', 'workflow_snapshot'],
        ];
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function replaceLegacyVariables(string $table, array $columns, bool $forward): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable($table)) {
            return;
        }

        $existingColumns = array_values(array_filter(
            $columns,
            static fn (string $column): bool => $schema->hasColumn($table, $column),
        ));

        if ($existingColumns === []) {
            return;
        }

        $database = $this->database();

        $database->table($table)
            ->select(array_merge(['id'], $existingColumns))
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($database, $table, $existingColumns, $forward): void {
                foreach ($rows as $row) {
                    $updates = [];

                    foreach ($existingColumns as $column) {
                        $current = $row->{$column} ?? null;

                        if ($current === null) {
                            continue;
                        }

                        $encoded = is_string($current) ? $current : json_encode($current);

                        if (! is_string($encoded) || $encoded === '') {
                            continue;
                        }

                        $next = $this->replaceVariableNames($encoded, $forward);

                        if ($next === $encoded) {
                            continue;
                        }

                        $updates[$column] = $next;
                    }

                    if ($updates !== []) {
                        $database->table($table)
                            ->where('id', $row->id)
                            ->update($updates);
                    }
                }
            });
    }

    private function replaceVariableNames(string $payload, bool $forward): string
    {
        $replacements = $forward
            ? [
                'rag_context' => 'knowledge_context',
                'rag_answer' => 'knowledge_answer',
            ]
            : [
                'knowledge_context' => 'rag_context',
                'knowledge_answer' => 'rag_answer',
            ];

        return str_replace(array_keys($replacements), array_values($replacements), $payload);
    }
};
