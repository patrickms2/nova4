<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        if ($this->schema()->hasTable('agent_workflows')) {
            return;
        }

        $this->ensureTablesExist(['agentic_bots'], 'create agent_workflows');

        $this->schema()->create('agent_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('workflow_data')->nullable();
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->foreignId('bot_id')
                ->nullable()
                ->constrained('agentic_bots')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->schema()->dropIfExists('agent_workflows');
    }
};
