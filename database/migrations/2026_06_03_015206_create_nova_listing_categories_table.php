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
        Schema::create('nova_listing_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nova_business_id')->constrained('nova_businesses')->cascadeOnDelete();
            $table->foreignId('server_id')->nullable()->constrained('servers')->nullOnDelete();
            $table->foreignId('tool_id')->nullable()->constrained('tools')->nullOnDelete();
            $table->string('slug');                        // hotel|restaurant|visit|route|product
            $table->json('keywords');                      // ["hotel","hoteles","alojamiento"]
            $table->json('system_names')->nullable();       // names to skip as location (brand names)
            $table->text('intro_text')->nullable();         // "Hoteles activos en Taxilanz:"
            $table->text('cta_text')->nullable();           // "¿En cuál solicito taxi?"
            $table->string('count_label')->nullable();      // "hoteles" (for count replies)
            $table->json('tool_params')->nullable();        // {"input":{"per_page":"20"}}
            $table->json('item_fields')->nullable();        // {"name":"nombre","city":"poblacion"}
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['nova_business_id', 'slug']);
            $table->index(['nova_business_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nova_listing_categories');
    }
};
