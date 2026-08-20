<?php

use Heiner\FilamentAgenticChatbot\Models\DataResourceDefinition;
use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        if ($this->schema()->hasTable('agentic_data_resources')) {
            return;
        }

        $this->schema()->create('agentic_data_resources', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('model');
            $table->json('allowed_modes')->nullable();
            $table->json('fields');
            $table->json('scope_filters')->nullable();
            $table->json('default_sort')->nullable();
            $table->unsignedInteger('default_limit')->default(10);
            $table->unsignedInteger('max_limit')->default(25);
            $table->text('query_guidance')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['is_active', 'key'], 'agentic_data_resources_active_key_index');
        });

        $this->syncConfiguredSources();
    }

    public function down(): void
    {
        $this->schema()->dropIfExists('agentic_data_resources');
    }

    protected function syncConfiguredSources(): void
    {
        foreach ((array) config('filament-agentic-chatbot.data_resources.resources', []) as $key => $resource) {
            if (! is_string($key) || ! is_array($resource)) {
                continue;
            }

            $attributes = DataResourceDefinition::attributesFromDataResourceConfig($key, $resource);

            if (($attributes['model'] ?? '') === '' || ($attributes['fields'] ?? []) === []) {
                continue;
            }

            $payload = $attributes;

            foreach (['allowed_modes', 'fields', 'scope_filters', 'default_sort'] as $jsonField) {
                $payload[$jsonField] = json_encode($payload[$jsonField] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $payload['created_at'] = now();
            $payload['updated_at'] = now();

            $this->database()->table('agentic_data_resources')->updateOrInsert(
                ['key' => $attributes['key']],
                $payload,
            );
        }
    }
};
