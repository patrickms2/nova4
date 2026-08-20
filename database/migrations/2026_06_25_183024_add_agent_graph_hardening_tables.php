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
        Schema::dropIfExists(config('agent-graph.tables.node_executions', 'agent_graph_node_executions'));

        Schema::table(config('agent-graph.tables.interrupts', 'agent_graph_interrupts'), function (Blueprint $table): void {
            $table->dropIndex(['expires_at']);
            $table->dropColumn('expires_at');
        });
    }
};
