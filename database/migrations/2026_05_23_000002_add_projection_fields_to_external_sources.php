<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_sources', function (Blueprint $table): void {
            $table->string('resource_type')->nullable()->after('source_label');
            $table->string('target_model')->nullable()->after('resource_type');
            $table->string('sync_direction')->default('remote_to_local')->after('target_model');
            $table->string('capability')->nullable()->after('sync_direction');
            $table->index(['resource_type', 'target_model']);
        });
    }

    public function down(): void
    {
        Schema::table('external_sources', function (Blueprint $table): void {
            $table->dropIndex(['resource_type', 'target_model']);
            $table->dropColumn(['resource_type', 'target_model', 'sync_direction', 'capability']);
        });
    }
};
