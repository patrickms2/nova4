<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nova_mcp_servers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('nova_business_id')->constrained('nova_businesses')->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->index();
            $table->string('endpoint_url');
            $table->string('auth_type')->default('api_key');
            $table->string('status')->default('draft')->index();
            $table->json('capabilities')->nullable();
            $table->json('credentials')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['nova_business_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nova_mcp_servers');
    }
};
