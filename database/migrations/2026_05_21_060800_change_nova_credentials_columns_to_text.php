<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasTable('nova_mcp_servers')) {
            DB::statement('ALTER TABLE nova_mcp_servers MODIFY credentials LONGTEXT NULL');
        }

        if (Schema::hasTable('nova_whatsapp_channels')) {
            DB::statement('ALTER TABLE nova_whatsapp_channels MODIFY credentials LONGTEXT NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasTable('nova_mcp_servers')) {
            DB::statement('ALTER TABLE nova_mcp_servers MODIFY credentials JSON NULL');
        }

        if (Schema::hasTable('nova_whatsapp_channels')) {
            DB::statement('ALTER TABLE nova_whatsapp_channels MODIFY credentials JSON NULL');
        }
    }
};
