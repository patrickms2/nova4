<?php

declare(strict_types=1);

namespace Tests\Feature\Voodflow;

use App\Domain\Nova\Studio\Workspace\WorkspaceBuilder;
use App\Models\NovaGraphLayout;
use App\Models\User;
use Nova\NovaHub\Pages\LiveSystemGraph;
use Tests\TestCase;

/**
 * Proves the Live System Graph can be rendered through Voodflow's
 * canvas (VoodflowAdapter) inside the existing Filament `app` panel,
 * fed by canonical NOVA Definition data (GraphBuilder), without
 * Voodflow becoming a second source of truth.
 *
 * See .codex/tasks/029-NOVA Voodflow Action Nodes.md and
 * NOVA_GRAPH.md → "VOODFLOW".
 */
class LiveSystemGraphPageTest extends TestCase
{
    public function test_it_renders_real_nova_nodes_reconstructed_from_the_active_workspace(): void
    {
        $workspace = app(WorkspaceBuilder::class)->build('hotel');
        $this->withSession(['nova.workspace' => $workspace]);

        $page = new LiveSystemGraph;
        $data = $page->getViewData();

        $ids = array_column($data['nodes'], 'id');

        $this->assertContains('studio.graph.node.workspace', $ids);
        $this->assertContains('studio.graph.node.capability.reservations', $ids);
        $this->assertContains('studio.graph.node.resource.reserva', $ids);

        $edge = collect($data['edges'])->first(
            fn (array $edge): bool => $edge['source'] === 'studio.graph.node.workspace'
                && $edge['target'] === 'studio.graph.node.capability.reservations',
        );
        $this->assertSame('owns', $edge['label']);
    }

    public function test_it_uses_stored_layout_position_when_present(): void
    {
        $workspace = app(WorkspaceBuilder::class)->build('hotel');
        $this->withSession(['nova.workspace' => $workspace]);

        NovaGraphLayout::query()->create([
            'workspace_id' => $workspace['id'],
            'node_address' => 'studio.graph.node.workspace',
            'x' => 321,
            'y' => 654,
        ]);

        $page = new LiveSystemGraph;
        $data = $page->getViewData();

        $node = collect($data['nodes'])->firstWhere('id', 'studio.graph.node.workspace');

        $this->assertSame(['x' => 321, 'y' => 654], $node['position']);
    }

    public function test_it_falls_back_to_a_default_grid_position_without_stored_layout(): void
    {
        $workspace = app(WorkspaceBuilder::class)->build('hotel');
        $this->withSession(['nova.workspace' => $workspace]);

        $page = new LiveSystemGraph;
        $data = $page->getViewData();

        $node = collect($data['nodes'])->firstWhere('id', 'studio.graph.node.workspace');

        $this->assertIsInt($node['position']['x']);
        $this->assertIsInt($node['position']['y']);
    }

    public function test_it_renders_empty_state_without_an_active_workspace(): void
    {
        $page = new LiveSystemGraph;
        $data = $page->getViewData();

        $this->assertSame([], $data['nodes']);
        $this->assertSame([], $data['edges']);
    }

    public function test_the_page_is_reachable_inside_the_app_panel(): void
    {
        $workspace = app(WorkspaceBuilder::class)->build('hotel');
        $this->withSession(['nova.workspace' => $workspace]);

        $this->actingAs(User::factory()->create())
            ->get('/app/live-system-graph')
            ->assertOk()
            ->assertSee('nova-live-system-graph', false);
    }
}
