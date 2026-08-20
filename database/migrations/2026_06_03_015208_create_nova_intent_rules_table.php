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
        Schema::create('nova_intent_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nova_business_id')->nullable()->constrained('nova_businesses')->nullOnDelete();
            $table->string('intent_key');                   // commercial_info|system_info|restaurant_booking
            $table->enum('rule_type', ['include', 'exclude', 'system_topic']);
            $table->json('keywords');                       // ["listado","lista","ver","activos"]
            $table->string('description')->nullable();      // human-readable label
            $table->unsignedTinyInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['nova_business_id', 'intent_key', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nova_intent_rules');
    }
};
