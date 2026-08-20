<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nova_presentation_nodes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('representation_id')->constrained('nova_representations')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('nova_presentation_nodes')->cascadeOnDelete();

            $table->string('node_type');

            $table->foreignId('capability_id')->nullable()->constrained('nova_capabilities')->nullOnDelete();
            $table->foreignId('relation_id')->nullable()->constrained('nova_relations')->nullOnDelete();
            $table->foreignId('resource_id')->nullable()->constrained('nova_resources')->nullOnDelete();

            $table->string('key');
            $table->string('label');
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('visible')->default(true);
            $table->json('settings')->nullable();

            $table->timestamps();

            $table->unique(['representation_id', 'key']);
            $table->index(['representation_id', 'parent_id', 'sort'], 'nova_presentation_tree_idx');
            $table->index(['node_type', 'visible']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nova_presentation_nodes');
    }
};
