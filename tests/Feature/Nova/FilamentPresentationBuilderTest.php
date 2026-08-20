<?php

declare(strict_types=1);

namespace Tests\Feature\Nova;

use App\Enums\Nova\NovaPresentationNodeType;
use App\Enums\Nova\NovaRepresentationStatus;
use App\Enums\Nova\NovaRepresentationType;
use App\Enums\Nova\NovaResourceType;
use App\Models\Nova\NovaCapability;
use App\Models\Nova\NovaRepresentation;
use App\Models\Nova\NovaResource;
use App\Support\Nova\FilamentPresentationBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FilamentPresentationBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_builder_creates_table_form_and_infolist_nodes(): void
    {
        $resource = NovaResource::query()->create([
            'key' => 'incident',
            'name' => 'Incident',
            'type' => NovaResourceType::EloquentModel,
            'class_name' => 'App\\Models\\Incident',
            'settings' => [],
        ]);

        $capability = NovaCapability::query()->create([
            'key' => 'community.incidents',
            'name' => 'Incidencias',
            'status' => 'active',
            'settings' => [],
        ]);

        $representation = NovaRepresentation::query()->create([
            'resource_id' => $resource->id,
            'capability_id' => $capability->id,
            'type' => NovaRepresentationType::Filament,
            'status' => NovaRepresentationStatus::Matched,
            'key' => 'filament.incident',
            'name' => 'Incident',
            'class_name' => 'App\\Filament\\Resources\\IncidentResource',
            'model_class' => 'App\\Models\\Incident',
            'settings' => [],
        ]);

        $root = app(FilamentPresentationBuilder::class)->build($representation);

        $types = $root->children()->pluck('node_type')->map(
            fn ($value) => $value instanceof NovaPresentationNodeType ? $value->value : (string) $value
        )->all();

        $this->assertContains('table', $types);
        $this->assertContains('form', $types);
        $this->assertContains('infolist', $types);
    }
}
