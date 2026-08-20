<?php

use Heiner\AgentGraph\Persistence\AgentGraphMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends AgentGraphMigration
{
    public function up(): void
    {

    }

    public function down(): void
    {
        Schema::table(config('agent-graph.tables.node_executions', 'agent_graph_node_executions'), function (Blueprint $table): void {
            $table->dropUnique(['execution_id']);
            $table->dropIndex(['checkpoint_id']);
            $table->dropIndex(['interrupt_id']);
            $table->dropIndex(['locked_until']);
            $table->dropColumn([
                'execution_id',
                'checkpoint_id',
                'base_state',
                'node_state',
                'resume_payload',
                'interrupt_id',
                'locked_until',
                'started_at',
                'finished_at',
            ]);
        });
    }
};
