<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('workflow_runs')) {
            return;
        }

        if ($schema->hasColumn('workflow_runs', 'workflow_snapshot')) {
            return;
        }

        $schema->table('workflow_runs', function (Blueprint $table): void {
            $table->json('workflow_snapshot')->nullable()->after('meta');
        });
    }

    public function down(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('workflow_runs')) {
            return;
        }

        if (! $schema->hasColumn('workflow_runs', 'workflow_snapshot')) {
            return;
        }

        $schema->table('workflow_runs', function (Blueprint $table): void {
            $table->dropColumn('workflow_snapshot');
        });
    }
};
