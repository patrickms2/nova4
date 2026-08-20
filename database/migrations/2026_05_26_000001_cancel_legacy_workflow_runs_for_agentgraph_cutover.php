<?php

use Heiner\FilamentAgenticChatbot\Models\WorkflowRun;
use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Support\Carbon;

return new class extends PackageMigration
{
    public function up(): void
    {
        if (! $this->schema()->hasTable('workflow_runs')) {
            return;
        }

        $now = Carbon::now();

        $this->database()
            ->table('workflow_runs')
            ->whereIn('status', [
                WorkflowRun::STATUS_RUNNING,
                WorkflowRun::STATUS_HALTED,
                WorkflowRun::STATUS_DELAYED,
            ])
            ->orderBy('id')
            ->chunkById(100, function ($runs) use ($now): void {
                foreach ($runs as $run) {
                    $meta = $this->decodeMeta($run->meta ?? null);

                    if (trim((string) data_get($meta, 'agent_graph.run_id', '')) !== '') {
                        continue;
                    }

                    $meta['legacy_cancelled_by_agentgraph_cutover'] = true;
                    $meta['legacy_cancelled_at'] = $now->toJSON();
                    $meta['system_failure_reason'] = 'agentgraph_only_cutover';

                    $this->database()
                        ->table('workflow_runs')
                        ->where('id', $run->id)
                        ->update([
                            'status' => WorkflowRun::STATUS_CANCELLED,
                            'halt_reason' => null,
                            'resume_at' => null,
                            'meta' => json_encode($meta, JSON_THROW_ON_ERROR),
                            'updated_at' => $now,
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Irreversible: the migration intentionally stops old runtime work.
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeMeta(mixed $meta): array
    {
        if (is_array($meta)) {
            return $meta;
        }

        if (! is_string($meta) || trim($meta) === '') {
            return [];
        }

        $decoded = json_decode($meta, true);

        return is_array($decoded) ? $decoded : [];
    }
};
