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
        if (! Schema::hasTable('nova_mcp_servers')) {
            return;
        }

        $novaMcpServers = DB::table('nova_mcp_servers')->get();

        foreach ($novaMcpServers as $novaServer) {
            $slug = Str::slug($novaServer->name);
            $endpoint = $novaServer->endpoint_url;

            $existing = DB::table('servers')
                ->where('nova_business_id', $novaServer->nova_business_id)
                ->where('slug', $slug)
                ->first();

            if ($existing) {
                DB::table('servers')->where('id', $existing->id)->update([
                    'nova_service_id' => $novaServer->nova_service_id,
                    'type' => $novaServer->type,
                    'auth_type' => $novaServer->auth_type,
                    'credentials' => $novaServer->credentials,
                    'status' => $novaServer->status,
                    'capabilities' => $novaServer->capabilities,
                    'last_checked_at' => $novaServer->last_checked_at,
                    'last_error' => $novaServer->last_error,
                    'updated_at' => now(),
                ]);
            } else {
                $uniqueSlug = $slug;
                $counter = 1;
                while (DB::table('servers')->where('slug', $uniqueSlug)->exists()) {
                    $uniqueSlug = $slug . '-' . $counter++;
                }

                DB::table('servers')->insert([
                    'nova_business_id' => $novaServer->nova_business_id,
                    'nova_service_id' => $novaServer->nova_service_id,
                    'name' => $novaServer->name,
                    'slug' => $uniqueSlug,
                    'type' => $novaServer->type,
                    'description' => null,
                    'version' => '1.0.0',
                    'instructions' => null,
                    'transport' => 'web',
                    'auth_type' => $novaServer->auth_type,
                    'credentials' => $novaServer->credentials,
                    'endpoint' => $endpoint,
                    'middleware' => null,
                    'metadata' => null,
                    'is_active' => $novaServer->status === 'active',
                    'status' => $novaServer->status,
                    'capabilities' => $novaServer->capabilities,
                    'last_checked_at' => $novaServer->last_checked_at,
                    'last_error' => $novaServer->last_error,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is data-only; rollback would require complex logic
        // to identify which servers came from nova_mcp_servers.
        // In production, use a backup before running this migration.
    }
};
