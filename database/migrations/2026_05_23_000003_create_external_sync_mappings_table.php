<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_sync_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
            $table->foreignId('external_source_id')->constrained('external_sources')->cascadeOnDelete();
            $table->string('business_name')->nullable();
            $table->string('source_platform');
            $table->string('source_label');
            $table->string('resource_type');
            $table->string('target_model');
            $table->unsignedBigInteger('target_id');
            $table->string('external_id');
            $table->string('external_item_id')->nullable();
            $table->string('payload_hash')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['external_source_id', 'resource_type', 'external_id', 'external_item_id'],
                'external_sync_mapping_remote_unique'
            );
            $table->index(['target_model', 'target_id']);
            $table->index(['resource_type', 'source_platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_sync_mappings');
    }
};
