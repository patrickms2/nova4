<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('workflow_runs') || $schema->hasColumn('workflow_runs', 'node_traces')) {
            return;
        }

        $schema->table('workflow_runs', function (Blueprint $table): void {
            $table->json('node_traces')->nullable()->after('node_history');
        });
    }

    public function down(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('workflow_runs') || ! $schema->hasColumn('workflow_runs', 'node_traces')) {
            return;
        }

        $schema->table('workflow_runs', function (Blueprint $table): void {
            $table->dropColumn('node_traces');
        });
    }
};
