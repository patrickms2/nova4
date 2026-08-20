<?php

declare(strict_types=1);

namespace Tests\Feature\Nova;

use App\Enums\Nova\NovaRepresentationType;
use App\Support\Nova\NovaDefinitionService;
use App\Support\Nova\NovaRuntime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class NovaDefinitionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_community_definition_is_seeded_and_runtime_can_read_it(): void
    {
        app(NovaDefinitionService::class)->ensureCommunityDefinition();

        $this->assertTrue(app(NovaRuntime::class)->capabilityEnabled(
            'community',
            'owner',
            NovaRepresentationType::Livewire,
            'community.incidents',
        ));

        $this->assertTrue(app(NovaRuntime::class)->capabilityEnabled(
            'community',
            'manager',
            NovaRepresentationType::Filament,
            'community.incidents',
        ));

        $this->assertFalse(app(NovaRuntime::class)->capabilityEnabled(
            'community',
            'owner',
            NovaRepresentationType::Filament,
            'community.incidents',
        ));
    }
}
