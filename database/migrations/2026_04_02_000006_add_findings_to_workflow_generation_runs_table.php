<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('workflow_generation_runs')) {
            return;
        }

        $schema->table('workflow_generation_runs', function (Blueprint $table) use ($schema): void {
            if (! $schema->hasColumn('workflow_generation_runs', 'findings')) {
                $table->json('findings')->nullable()->after('errors');
            }
        });
    }

    public function down(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('workflow_generation_runs') || ! $schema->hasColumn('workflow_generation_runs', 'findings')) {
            return;
        }

        $schema->table('workflow_generation_runs', function (Blueprint $table): void {
            $table->dropColumn('findings');
        });
    }
};
