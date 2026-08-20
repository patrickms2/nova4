<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nova_workspaces', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('nova_panels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained('nova_workspaces')->cascadeOnDelete();
            $table->string('key');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['workspace_id', 'key']);
        });

        Schema::create('nova_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('panel_id')->constrained('nova_panels')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('nova_groups')->nullOnDelete();
            $table->string('key');
            $table->string('name');
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['panel_id', 'key']);
        });

        Schema::create('nova_capabilities', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('status')->default('active');
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('nova_tools', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('capability_id')->constrained('nova_capabilities')->cascadeOnDelete();
            $table->string('key');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type');
            $table->string('handler')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['capability_id', 'key']);
        });

        Schema::create('nova_resources', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type');
            $table->string('class_name')->nullable();
            $table->string('source')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('nova_relations', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->foreignId('source_resource_id')->constrained('nova_resources')->cascadeOnDelete();
            $table->foreignId('target_resource_id')->constrained('nova_resources')->cascadeOnDelete();
            $table->string('type');
            $table->string('relation_name')->nullable();
            $table->string('foreign_key')->nullable();
            $table->string('local_key')->nullable();
            $table->string('inverse_relation_name')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('nova_connectors', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type');
            $table->string('direction')->default('bidirectional');
            $table->string('adapter')->nullable();
            $table->string('endpoint')->nullable();
            $table->string('status')->default('active');
            $table->string('credentials_key')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('nova_capability_resource', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('capability_id')->constrained('nova_capabilities')->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained('nova_resources')->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['capability_id', 'resource_id']);
        });

        Schema::create('nova_capability_connector', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('capability_id')->constrained('nova_capabilities')->cascadeOnDelete();
            $table->foreignId('connector_id')->constrained('nova_connectors')->cascadeOnDelete();
            $table->string('direction')->default('bidirectional');
            $table->unsignedInteger('sort')->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['capability_id', 'connector_id']);
        });

        Schema::create('nova_bindings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('panel_id')->constrained('nova_panels')->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('nova_groups')->nullOnDelete();
            $table->foreignId('capability_id')->nullable()->constrained('nova_capabilities')->cascadeOnDelete();
            $table->foreignId('tool_id')->nullable()->constrained('nova_tools')->cascadeOnDelete();
            $table->foreignId('resource_id')->nullable()->constrained('nova_resources')->cascadeOnDelete();
            $table->foreignId('relation_id')->nullable()->constrained('nova_relations')->cascadeOnDelete();
            $table->foreignId('connector_id')->nullable()->constrained('nova_connectors')->cascadeOnDelete();
            $table->string('target_type');
            $table->string('role');
            $table->string('representation');
            $table->boolean('visible')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['panel_id', 'role', 'representation', 'visible'], 'nova_bindings_runtime_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nova_bindings');
        Schema::dropIfExists('nova_capability_connector');
        Schema::dropIfExists('nova_capability_resource');
        Schema::dropIfExists('nova_connectors');
        Schema::dropIfExists('nova_relations');
        Schema::dropIfExists('nova_resources');
        Schema::dropIfExists('nova_tools');
        Schema::dropIfExists('nova_capabilities');
        Schema::dropIfExists('nova_groups');
        Schema::dropIfExists('nova_panels');
        Schema::dropIfExists('nova_workspaces');
    }
};
