<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('bot_compound_requests')) {
            return;
        }

        $schema->table('bot_compound_requests', function (Blueprint $table) use ($schema): void {
            if (! $schema->hasColumn('bot_compound_requests', 'agent_graph_run_id')) {
                $table->string('agent_graph_run_id')->nullable()->index();
            }

            if (! $schema->hasColumn('bot_compound_requests', 'agent_graph_thread_id')) {
                $table->string('agent_graph_thread_id')->nullable()->index();
            }

            if (! $schema->hasColumn('bot_compound_requests', 'agent_graph_interrupt_id')) {
                $table->string('agent_graph_interrupt_id')->nullable()->index();
            }

            if (! $schema->hasColumn('bot_compound_requests', 'agent_graph_checkpoint_id')) {
                $table->string('agent_graph_checkpoint_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('bot_compound_requests')) {
            return;
        }

        $schema->table('bot_compound_requests', function (Blueprint $table) use ($schema): void {
            foreach ([
                'agent_graph_checkpoint_id',
                'agent_graph_interrupt_id',
                'agent_graph_thread_id',
                'agent_graph_run_id',
            ] as $column) {
                if ($schema->hasColumn('bot_compound_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
