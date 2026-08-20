<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        $schema = $this->schema();

        foreach (['agentic_bots', 'bot_knowledge_sources', 'agent_workflows'] as $table) {
            if ($schema->hasTable($table) && ! $schema->hasColumn($table, 'deleted_at')) {
                $schema->table($table, function (Blueprint $table): void {
                    $table->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        $schema = $this->schema();

        foreach (['agentic_bots', 'bot_knowledge_sources', 'agent_workflows'] as $table) {
            if ($schema->hasTable($table) && $schema->hasColumn($table, 'deleted_at')) {
                $schema->table($table, function (Blueprint $table): void {
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};
