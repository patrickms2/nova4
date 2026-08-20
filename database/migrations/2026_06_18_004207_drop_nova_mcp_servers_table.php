<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('nova_mcp_servers')) {
            Schema::dropIfExists('nova_mcp_servers');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is destructive; rollback would require recreating the table
        // and migrating data back from servers, which is complex.
        // In production, use a backup before running this migration.
    }
};
