<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('nova_representations')) {
            return;
        }

        Schema::create('nova_representations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->nullable()->constrained('nova_workspaces')->nullOnDelete();
            $table->foreignId('panel_id')->nullable()->constrained('nova_panels')->nullOnDelete();
            $table->foreignId('resource_id')->nullable()->constrained('nova_resources')->nullOnDelete();
            $table->foreignId('capability_id')->nullable()->constrained('nova_capabilities')->nullOnDelete();

            $table->string('type');
            $table->string('status')->default('detected');
            $table->string('key')->unique();
            $table->string('name');
            $table->string('class_name')->unique();
            $table->string('model_class')->nullable();

            $table->string('navigation_group')->nullable();
            $table->string('navigation_label')->nullable();
            $table->string('navigation_icon')->nullable();
            $table->integer('navigation_sort')->nullable();

            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index(['panel_id', 'capability_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nova_representations');
    }
};
