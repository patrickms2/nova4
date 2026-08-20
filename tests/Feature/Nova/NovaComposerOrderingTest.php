<?php

declare(strict_types=1);

namespace Tests\Feature\Nova;

use App\Enums\Nova\NovaBindingTarget;
use App\Enums\Nova\NovaRepresentationType;
use App\Models\Nova\NovaBinding;
use App\Support\Nova\NovaDefinitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class NovaComposerOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_filament_manager_bindings_exist_and_are_orderable(): void
    {
        app(NovaDefinitionService::class)->ensureCommunityDefinition();

        $bindings = NovaBinding::query()
            ->where('target_type', NovaBindingTarget::Capability)
            ->where('role', 'manager')
            ->where('representation', NovaRepresentationType::Filament)
            ->orderBy('sort')
            ->get();

        $this->assertNotEmpty($bindings);
        $this->assertTrue($bindings->every(fn (NovaBinding $binding): bool => $binding->group_id !== null));
    }
}
