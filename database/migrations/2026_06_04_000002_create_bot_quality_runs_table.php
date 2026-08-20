<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        if ($this->schema()->hasTable('bot_quality_runs')) {
            return;
        }

        $this->ensureTablesExist(
            ['bot_quality_scenarios', 'agentic_bots', 'agent_workflows', 'workflow_runs'],
            'create bot quality runs',
        );

        $this->schema()->create('bot_quality_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bot_quality_scenario_id')->constrained('bot_quality_scenarios')->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained('agentic_bots')->cascadeOnDelete();
            $table->foreignId('agent_workflow_id')->nullable()->constrained('agent_workflows')->nullOnDelete();
            $table->foreignId('workflow_run_id')->nullable()->constrained('workflow_runs')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->string('target')->default('direct_bot');
            $table->string('workflow_draft_fingerprint', 64)->nullable();
            $table->string('scenario_fingerprint', 64)->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->json('checks')->nullable();
            $table->text('response_excerpt')->nullable();
            $table->text('failure_summary')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('cost_cents')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['bot_id', 'status']);
            $table->index(['agent_workflow_id', 'status']);
            $table->index(['agent_workflow_id', 'workflow_draft_fingerprint'], 'bot_quality_runs_workflow_draft_fingerprint_index');
            $table->index(['bot_quality_scenario_id', 'scenario_fingerprint'], 'bot_quality_runs_scenario_fingerprint_index');
            $table->index(['bot_quality_scenario_id', 'created_at']);
        });
    }

    public function down(): void
    {
        $this->schema()->dropIfExists('bot_quality_runs');
    }
};
