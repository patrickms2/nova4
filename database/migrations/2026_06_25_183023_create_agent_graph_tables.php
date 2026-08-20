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
        foreach (array_reverse(config('agent-graph.tables')) as $table) {
            Schema::dropIfExists($table);
        }
    }
};
