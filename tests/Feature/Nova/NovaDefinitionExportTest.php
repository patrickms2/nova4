<?php

declare(strict_types=1);

namespace Tests\Feature\Nova;

use App\Support\Nova\NovaDefinitionService;
use Database\Seeders\NovaStudioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class NovaDefinitionExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_definition_exports_all_seeded_workspaces(): void
    {
        $this->seed(NovaStudioSeeder::class);

        $definition = app(NovaDefinitionService::class)->exportAll();

        $this->assertSame('NOVA4', $definition['nova']);
        $this->assertGreaterThanOrEqual(5, count($definition['workspaces']));
        $this->assertNotEmpty($definition['workspaces'][0]['workspace']['panels']);
    }
}
