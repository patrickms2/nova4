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
        Schema::create('nova_intent_to_server_mapping', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nova_business_id')->nullable()->constrained('nova_businesses')->nullOnDelete();
            $table->string('intent_key', 50);
            $table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
            $table->foreignId('tool_id')->nullable()->constrained('tools')->nullOnDelete();
            $table->integer('priority')->default(0);
            $table->json('conditions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['intent_key', 'is_active']);
            $table->index(['nova_business_id', 'intent_key']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nova_intent_to_server_mapping');
    }
};
