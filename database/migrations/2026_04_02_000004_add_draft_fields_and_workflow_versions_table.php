<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        $schema = $this->schema();

        $this->ensureTablesExist(['agent_workflows'], 'upgrade agent_workflows');

        if (! $schema->hasTable('agent_workflow_versions')) {
            $schema->create('agent_workflow_versions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('agent_workflow_id')
                    ->constrained('agent_workflows')
                    ->cascadeOnDelete();
                $table->unsignedInteger('version_number');
                $table->json('workflow_data');
                $table->unsignedSmallInteger('schema_version')->default(1);
                $table->string('source', 32)->default('publish');
                $table->timestamps();

                $table->unique(['agent_workflow_id', 'version_number'], 'agent_workflow_versions_workflow_version_unique');
            });
        }

        $schema->table('agent_workflows', function (Blueprint $table) use ($schema): void {
            if (! $schema->hasColumn('agent_workflows', 'draft_workflow_data')) {
                $table->json('draft_workflow_data')->nullable();
            }

            if (! $schema->hasColumn('agent_workflows', 'draft_schema_version')) {
                $table->unsignedSmallInteger('draft_schema_version')->nullable();
            }

            if (! $schema->hasColumn('agent_workflows', 'draft_saved_at')) {
                $table->timestamp('draft_saved_at')->nullable();
            }

            if (! $schema->hasColumn('agent_workflows', 'published_at')) {
                $table->timestamp('published_at')->nullable();
            }
        });

        $workflows = $this->database()->table('agent_workflows')
            ->select(['id', 'workflow_data', 'schema_version', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->get();

        foreach ($workflows as $workflow) {
            $payload = $this->normalizePayload($workflow->workflow_data);
            $schemaVersion = (int) ($workflow->schema_version ?? 1);
            $timestamp = $workflow->updated_at ?? $workflow->created_at;

            $this->database()->table('agent_workflows')
                ->where('id', $workflow->id)
                ->update([
                    'draft_workflow_data' => $payload !== null ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                    'draft_schema_version' => $payload !== null ? $schemaVersion : null,
                    'draft_saved_at' => $timestamp,
                    'published_at' => $payload !== null ? $timestamp : null,
                ]);

            if ($payload === null) {
                continue;
            }

            $versionExists = $this->database()->table('agent_workflow_versions')
                ->where('agent_workflow_id', $workflow->id)
                ->where('version_number', 1)
                ->exists();

            if ($versionExists) {
                continue;
            }

            $this->database()->table('agent_workflow_versions')->insert([
                'agent_workflow_id' => $workflow->id,
                'version_number' => 1,
                'workflow_data' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'schema_version' => $schemaVersion,
                'source' => 'initial',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }
    }

    public function down(): void
    {
        $schema = $this->schema();

        $schema->dropIfExists('agent_workflow_versions');

        if (! $schema->hasTable('agent_workflows')) {
            return;
        }

        $schema->table('agent_workflows', function (Blueprint $table) use ($schema): void {
            $dropColumns = [];

            foreach (['draft_workflow_data', 'draft_schema_version', 'draft_saved_at', 'published_at'] as $column) {
                if ($schema->hasColumn('agent_workflows', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function normalizePayload(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
};
