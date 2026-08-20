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
        Schema::create('nova_cross_selling_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_business_id')->constrained('nova_businesses')->cascadeOnDelete();
            $table->foreignId('to_business_id')->constrained('nova_businesses')->cascadeOnDelete();
            $table->string('trigger_intent');               // winery_visit|restaurant_booking|product_info
            $table->text('message');                        // suggestion text shown to user
            $table->string('cta_label')->nullable();        // "¿Te interesa visitar...?"
            $table->string('cta_url')->nullable();
            $table->unsignedTinyInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['from_business_id', 'trigger_intent', 'is_active'], 'nova_cross_sell_from_intent_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nova_cross_selling_rules');
    }
};
