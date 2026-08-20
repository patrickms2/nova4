<?php

declare(strict_types=1);

namespace Tests\Feature\Nova;

use App\Models\Nova\NovaCapability;
use App\Models\Nova\NovaConnector;
use App\Models\Nova\NovaRelation;
use App\Models\Nova\NovaResource;
use App\Models\Nova\NovaWorkspace;
use Database\Seeders\NovaStudioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class NovaStudioSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_nova_studio_seed_is_idempotent(): void
    {
        $this->seed(NovaStudioSeeder::class);
        $this->seed(NovaStudioSeeder::class);

        $this->assertDatabaseHas('nova_workspaces', ['key'=>'community']);
        $this->assertDatabaseHas('nova_workspaces', ['key'=>'property']);
        $this->assertDatabaseHas('nova_workspaces', ['key'=>'rent']);
        $this->assertDatabaseHas('nova_workspaces', ['key'=>'access']);
        $this->assertDatabaseHas('nova_workspaces', ['key'=>'business']);

        $this->assertTrue(NovaWorkspace::query()->count() >= 5);
        $this->assertTrue(NovaCapability::query()->count() >= 40);
        $this->assertTrue(NovaResource::query()->count() >= 10);
        $this->assertTrue(NovaRelation::query()->count() >= 5);
        $this->assertTrue(NovaConnector::query()->count() >= 5);
    }
}
