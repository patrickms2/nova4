<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        if ($this->schema()->hasTable('bot_quality_scenarios')) {
            return;
        }

        $this->ensureTablesExist(['agentic_bots', 'agent_workflows'], 'create bot quality scenarios');

        $this->schema()->create('bot_quality_scenarios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bot_id')->constrained('agentic_bots')->cascadeOnDelete();
            $table->foreignId('agent_workflow_id')->nullable()->constrained('agent_workflows')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('user_message');
            $table->json('context_messages')->nullable();
            $table->json('expectations')->nullable();
            $table->boolean('is_blocking')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();

            $table->index(['bot_id', 'is_active']);
            $table->index(['agent_workflow_id', 'is_active']);
        });
    }

    public function down(): void
    {
        $this->schema()->dropIfExists('bot_quality_scenarios');
    }
};
