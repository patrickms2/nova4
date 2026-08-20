<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('bot_quality_runs')) {
            return;
        }

        $schema->table('bot_quality_runs', function (Blueprint $table) use ($schema): void {
            if (! $schema->hasColumn('bot_quality_runs', 'workflow_draft_fingerprint')) {
                $table->string('workflow_draft_fingerprint', 64)->nullable()->after('target');
            }

            if (! $schema->hasColumn('bot_quality_runs', 'scenario_fingerprint')) {
                $table->string('scenario_fingerprint', 64)->nullable()->after('workflow_draft_fingerprint');
            }
        });

        $schema->table('bot_quality_runs', function (Blueprint $table) use ($schema): void {
            if (
                $schema->hasColumn('bot_quality_runs', 'agent_workflow_id')
                && $schema->hasColumn('bot_quality_runs', 'workflow_draft_fingerprint')
                && ! $schema->hasIndex('bot_quality_runs', 'bot_quality_runs_workflow_draft_fingerprint_index')
            ) {
                $table->index(['agent_workflow_id', 'workflow_draft_fingerprint'], 'bot_quality_runs_workflow_draft_fingerprint_index');
            }

            if (
                $schema->hasColumn('bot_quality_runs', 'bot_quality_scenario_id')
                && $schema->hasColumn('bot_quality_runs', 'scenario_fingerprint')
                && ! $schema->hasIndex('bot_quality_runs', 'bot_quality_runs_scenario_fingerprint_index')
            ) {
                $table->index(['bot_quality_scenario_id', 'scenario_fingerprint'], 'bot_quality_runs_scenario_fingerprint_index');
            }
        });
    }

    public function down(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('bot_quality_runs')) {
            return;
        }

        $schema->table('bot_quality_runs', function (Blueprint $table) use ($schema): void {
            if ($schema->hasIndex('bot_quality_runs', 'bot_quality_runs_workflow_draft_fingerprint_index')) {
                $table->dropIndex('bot_quality_runs_workflow_draft_fingerprint_index');
            }

            if ($schema->hasIndex('bot_quality_runs', 'bot_quality_runs_scenario_fingerprint_index')) {
                $table->dropIndex('bot_quality_runs_scenario_fingerprint_index');
            }
        });

        $schema->table('bot_quality_runs', function (Blueprint $table) use ($schema): void {
            foreach (['workflow_draft_fingerprint', 'scenario_fingerprint'] as $column) {
                if ($schema->hasColumn('bot_quality_runs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
