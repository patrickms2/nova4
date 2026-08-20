<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('agent_workflow_versions')) {
            return;
        }

        $schema->table('agent_workflow_versions', function (Blueprint $table) use ($schema): void {
            if (! $schema->hasColumn('agent_workflow_versions', 'publish_note')) {
                $table->text('publish_note')->nullable()->after('source');
            }

            if (! $schema->hasColumn('agent_workflow_versions', 'actor_name')) {
                $table->string('actor_name')->nullable()->after('publish_note');
            }

            if (! $schema->hasColumn('agent_workflow_versions', 'actor_identifier')) {
                $table->string('actor_identifier')->nullable()->after('actor_name');
            }
        });
    }

    public function down(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('agent_workflow_versions')) {
            return;
        }

        $schema->table('agent_workflow_versions', function (Blueprint $table) use ($schema): void {
            $dropColumns = [];

            foreach (['publish_note', 'actor_name', 'actor_identifier'] as $column) {
                if ($schema->hasColumn('agent_workflow_versions', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
