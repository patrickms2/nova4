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
        Schema::create('nova_ai_knowledge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nova_business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('nova_ai_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->longText('content');
            $table->string('status')->default('pending');
            $table->json('metadata')->nullable();
            $table->longText('embedding')->nullable();
            $table->timestamp('vectorized_at')->nullable();
            $table->timestamps();

            $table->index(['nova_business_id', 'status']);
            $table->index(['nova_ai_profile_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nova_ai_knowledge');
    }
};
