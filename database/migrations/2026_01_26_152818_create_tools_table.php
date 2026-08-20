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
        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g., 'get-weather'
            $table->string('title');
            $table->text('description');
            $table->json('input_schema')->nullable(); // JSON Schema for parameters
            $table->json('output_schema')->nullable(); // Structured output schema
            $table->text('handler_code'); // PHP code for the tool logic
            $table->json('annotations')->nullable(); // isReadOnly, isIdempotent, etc.
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tools');
    }
};
