<?php
declare(strict_types=1);

namespace Tests\Feature\Nova;

use App\Enums\Nova\NovaBindingTarget;
use App\Enums\Nova\NovaRepresentationType;
use App\Enums\Nova\NovaResourceType;
use App\Enums\Nova\NovaToolType;
use App\Models\Nova\NovaBinding;
use App\Models\Nova\NovaCapability;
use App\Models\Nova\NovaGroup;
use App\Models\Nova\NovaPanel;
use App\Models\Nova\NovaResource;
use App\Models\Nova\NovaTool;
use App\Models\Nova\NovaWorkspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class NovaStudioDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_capability_can_bind_to_role_and_representation(): void
    {
        $workspace = NovaWorkspace::create(['key' => 'community', 'name' => 'NOVA Community']);
        $panel = NovaPanel::create(['workspace_id' => $workspace->id, 'key' => 'community', 'name' => 'Community']);
        $group = NovaGroup::create(['panel_id' => $panel->id, 'key' => 'community', 'name' => 'Comunidad']);
        $capability = NovaCapability::create(['key' => 'community.incidents', 'name' => 'Incidencias']);
        $tool = NovaTool::create([
            'capability_id' => $capability->id,
            'key' => 'create',
            'name' => 'Crear incidencia',
            'type' => NovaToolType::Action,
        ]);
        $resource = NovaResource::create([
            'key' => 'incident',
            'name' => 'Incident',
            'type' => NovaResourceType::EloquentModel,
            'class_name' => 'App\\Models\\Incident',
        ]);

        $capability->resources()->attach($resource, ['sort' => 10]);

        $binding = NovaBinding::create([
            'panel_id' => $panel->id,
            'group_id' => $group->id,
            'capability_id' => $capability->id,
            'tool_id' => $tool->id,
            'target_type' => NovaBindingTarget::Tool,
            'role' => 'owner',
            'representation' => NovaRepresentationType::Livewire,
            'visible' => true,
            'sort' => 20,
        ]);

        $this->assertTrue($binding->visible);
        $this->assertSame(NovaRepresentationType::Livewire, $binding->representation);
        $this->assertSame('community.incidents', $binding->capability->key);
        $this->assertSame('incident', $capability->resources->first()->key);
    }
}
