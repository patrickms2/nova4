<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        if ($this->schema()->hasTable('workflow_generation_runs')) {
            return;
        }

        $this->ensureTablesExist(['agent_workflows', 'agentic_bots'], 'create workflow_generation_runs');

        $this->schema()->create('workflow_generation_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('agent_workflow_id')
                ->constrained('agent_workflows')
                ->cascadeOnDelete();
            $table->foreignId('bot_id')
                ->nullable()
                ->constrained('agentic_bots')
                ->nullOnDelete();
            $table->string('status', 32)->default('queued');
            $table->text('prompt');
            $table->json('payload')->nullable();
            $table->json('errors')->nullable();
            $table->string('error_kind', 32)->nullable();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['agent_workflow_id', 'status']);
            $table->index(['agent_workflow_id', 'applied_at']);
        });
    }

    public function down(): void
    {
        $this->schema()->dropIfExists('workflow_generation_runs');
    }
};
