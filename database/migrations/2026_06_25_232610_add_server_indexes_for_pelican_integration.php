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
        Schema::table('servers', function (Blueprint $table) {
            // Add indexes for performance on existing columns
            try {
                $table->index(['type', 'status'], 'servers_type_status_index');
            } catch (\Exception $e) {
                // Index might already exist, ignore error
            }

            try {
                $table->index(['nova_business_id', 'type'], 'servers_nova_business_id_type_index');
            } catch (\Exception $e) {
                // Index might already exist, ignore error
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropIndex('servers_type_status_index');
            $table->dropIndex('servers_nova_business_id_type_index');
        });
    }
};
