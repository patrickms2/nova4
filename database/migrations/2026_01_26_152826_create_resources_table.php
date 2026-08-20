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
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('title');
            $table->text('description');
            $table->string('uri'); // e.g., 'weather://resources/guidelines'
            $table->string('uri_template')->nullable(); // For dynamic resources
            $table->string('mime_type')->default('text/plain');
            $table->text('content')->nullable(); // Static content
            $table->text('handler_code')->nullable(); // Dynamic content generator
            $table->json('annotations')->nullable(); // audience, priority, lastModified
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
        Schema::dropIfExists('resources');
    }
};
