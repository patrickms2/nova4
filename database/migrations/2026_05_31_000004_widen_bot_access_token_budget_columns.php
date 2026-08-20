<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;

return new class extends PackageMigration
{
    /**
     * @var array<int, string>
     */
    private array $columns = [
        'max_input_tokens',
        'max_output_tokens',
        'monthly_token_budget',
        'monthly_cost_budget_cents',
    ];

    public function up(): void
    {
        if (! $this->schema()->hasTable('bot_access_tokens')) {
            return;
        }

        $driver = strtolower($this->database()->getDriverName());

        foreach ($this->columns as $column) {
            if (! $this->schema()->hasColumn('bot_access_tokens', $column)) {
                continue;
            }

            match ($driver) {
                'pgsql' => $this->database()->statement("ALTER TABLE bot_access_tokens ALTER COLUMN {$column} TYPE BIGINT USING {$column}::bigint"),
                'mysql', 'mariadb' => $this->database()->statement("ALTER TABLE bot_access_tokens MODIFY {$column} BIGINT UNSIGNED NULL"),
                // SQLite stores integer values dynamically; existing columns already accept 64-bit values.
                'sqlite' => null,
                default => null,
            };
        }
    }

    public function down(): void
    {
        //
    }
};
