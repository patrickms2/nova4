<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Nova\Studio\Workspace\Graph\GraphBuilder;
use App\Domain\Nova\Studio\Workspace\WorkspaceBuilder;
use App\Domain\Nova\Studio\Workspace\WorkspaceRegistry;
use App\Livewire\Nova\NovaGraph;
use Illuminate\Auth\Middleware\Authenticate;
use Livewire\Livewire;
use Tests\TestCase;

class NovaGraphTest extends TestCase
{
    public function test_graph_is_reconstructed_from_the_active_workspace_definition(): void
    {
        $workspace = app(WorkspaceBuilder::class)->build('hotel');

        $graph = app(GraphBuilder::class)->build($workspace);

        $addresses = array_column($graph['nodes'], 'address');

        $this->assertContains('studio.graph.node.workspace', $addresses);
        $this->assertContains('studio.graph.node.capability.reservations', $addresses);
        $this->assertContains('studio.graph.node.resource.reserva', $addresses);

        $ownsEdge = collect($graph['edges'])->first(
            fn (array $edge): bool => $edge['from'] === 'studio.graph.node.workspace'
                && $edge['to'] === 'studio.graph.node.capability.reservations',
        );
        $this->assertSame('owns', $ownsEdge['type']);

        $producesEdge = collect($graph['edges'])->first(
            fn (array $edge): bool => $edge['from'] === 'studio.graph.node.capability.reservations'
                && $edge['to'] === 'studio.graph.node.resource.reserva',
        );
        $this->assertSame('produces', $producesEdge['type']);
    }

    public function test_graph_renders_real_nodes_and_allows_inspecting_a_node(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        $workspace = app(WorkspaceBuilder::class)->build('hotel');
        $this->withSession(['nova.workspace' => $workspace]);

        Livewire::test(NovaGraph::class)
            ->assertOk()
            ->assertSee('Reserva')
            ->call('selectNode', 'studio.graph.node.resource.reserva')
            ->assertSet('selectedAddress', 'studio.graph.node.resource.reserva')
            ->assertSee('RESOURCE');
    }

    public function test_inspecting_a_node_shows_its_real_semantic_relations(): void
    {
        $workspace = app(WorkspaceBuilder::class)->build('hotel');
        $this->withSession(['nova.workspace' => $workspace]);

        $component = Livewire::test(NovaGraph::class)
            ->call('selectNode', 'studio.graph.node.capability.reservations');

        $relations = $component->instance()->relationsFor('studio.graph.node.capability.reservations');

        $this->assertNotEmpty($relations);
        $types = array_column(array_column($relations, 'data'), 'type');
        $this->assertContains('owns', $types);
        $this->assertContains('produces', $types);
    }

    public function test_layout_metadata_can_be_persisted_separately_from_semantic_data(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        $workspace = app(WorkspaceBuilder::class)->build('hotel');
        $this->withSession(['nova.workspace' => $workspace]);

        Livewire::test(NovaGraph::class)
            ->call('saveLayout', 'studio.graph.node.capability.reservations', 321, 654);

        $this->assertDatabaseHas('nova_graph_layouts', [
            'workspace_id' => $workspace['id'],
            'node_address' => 'studio.graph.node.capability.reservations',
            'x' => 321,
            'y' => 654,
        ]);
    }

    public function test_dragging_an_available_capability_onto_the_canvas_mutates_the_real_workspace(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        $workspace = app(WorkspaceBuilder::class)->build('hotel');
        $this->withSession(['nova.workspace' => $workspace]);

        $component = Livewire::test(NovaGraph::class);
        $available = array_column($component->instance()->availableCapabilities(), 'id');
        $this->assertNotEmpty($available);
        $improvementId = $available[0];

        $component->call('addCapability', $improvementId);

        $nodeIds = array_column($component->get('nodes'), 'id');
        $this->assertContains("studio.graph.node.capability.{$improvementId}", $nodeIds);

        $this->assertContains(
            $improvementId,
            app(WorkspaceRegistry::class)->active()['improvement_ids'],
        );
    }

    public function test_dropping_a_free_action_onto_a_capability_creates_a_custom_action_node(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        $workspace = app(WorkspaceBuilder::class)->build('hotel');
        $this->withSession(['nova.workspace' => $workspace]);

        $component = Livewire::test(NovaGraph::class)
            ->call('addCustomAction', 'Confirmar por teléfono', 'reservations');

        $labels = array_column(array_column($component->get('nodes'), 'data'), 'label');
        $this->assertContains('Confirmar por teléfono', $labels);

        $workspace = app(WorkspaceRegistry::class)->active();
        $this->assertNotEmpty($workspace['custom_actions']);
        $this->assertSame('Confirmar por teléfono', $workspace['custom_actions'][0]['label']);
    }

    public function test_connecting_two_existing_nodes_creates_a_custom_relation_that_survives_reload(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        $workspace = app(WorkspaceBuilder::class)->build('hotel');
        $this->withSession(['nova.workspace' => $workspace]);

        Livewire::test(NovaGraph::class)->call(
            'addRelation',
            'studio.graph.node.capability.reservations',
            'studio.graph.node.resource.pago',
            'requiere',
        );

        $reloaded = Livewire::test(NovaGraph::class);
        $edge = collect($reloaded->get('edges'))->first(
            fn (array $edge): bool => $edge['source'] === 'studio.graph.node.capability.reservations'
                && $edge['target'] === 'studio.graph.node.resource.pago',
        );

        $this->assertNotNull($edge);
        $this->assertSame('requiere', $edge['data']['type']);
    }

    public function test_deleting_a_capability_node_that_is_an_opt_in_improvement_removes_it(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        $workspace = app(WorkspaceBuilder::class)->build('hotel');
        $this->withSession(['nova.workspace' => $workspace]);

        $component = Livewire::test(NovaGraph::class)->call('addCapability', 'whatsapp');
        $this->assertContains('studio.graph.node.capability.whatsapp', array_column($component->get('nodes'), 'id'));

        $component->call('deleteNode', 'studio.graph.node.capability.whatsapp');

        $nodeIds = array_column($component->get('nodes'), 'id');
        $this->assertNotContains('studio.graph.node.capability.whatsapp', $nodeIds);

        $updated = app(WorkspaceRegistry::class)->active();
        $this->assertNotContains('whatsapp', $updated['capability_ids']);
    }

    public function test_deleting_a_capability_node_that_is_a_blueprint_default_area_is_a_no_op(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        $workspace = app(WorkspaceBuilder::class)->build('hotel');
        $this->withSession(['nova.workspace' => $workspace]);

        $component = Livewire::test(NovaGraph::class)
            ->call('deleteNode', 'studio.graph.node.capability.reservations');

        $nodeIds = array_column($component->get('nodes'), 'id');
        $this->assertContains('studio.graph.node.capability.reservations', $nodeIds);

        $updated = app(WorkspaceRegistry::class)->active();
        $this->assertContains('reservations', $updated['capability_ids']);
    }

    public function test_deleting_a_custom_action_node_removes_it_from_the_workspace(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        $workspace = app(WorkspaceBuilder::class)->build('hotel');
        $this->withSession(['nova.workspace' => $workspace]);

        $component = Livewire::test(NovaGraph::class)
            ->call('addCustomAction', 'Confirmar por teléfono', 'reservations');

        $actionNode = collect($component->get('nodes'))->first(
            fn (array $node): bool => $node['data']['label'] === 'Confirmar por teléfono',
        );
        $this->assertNotNull($actionNode);

        $component->call('deleteNode', $actionNode['id']);

        $nodeIds = array_column($component->get('nodes'), 'id');
        $this->assertNotContains($actionNode['id'], $nodeIds);

        $updated = app(WorkspaceRegistry::class)->active();
        $this->assertEmpty($updated['custom_actions'] ?? []);
    }

    public function test_deleting_the_workspace_node_is_a_no_op(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        $workspace = app(WorkspaceBuilder::class)->build('hotel');
        $this->withSession(['nova.workspace' => $workspace]);

        $component = Livewire::test(NovaGraph::class)
            ->call('deleteNode', 'studio.graph.node.workspace');

        $nodeIds = array_column($component->get('nodes'), 'id');
        $this->assertContains('studio.graph.node.workspace', $nodeIds);
    }

    public function test_deleting_a_derived_non_custom_action_node_is_a_no_op(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        $workspace = app(WorkspaceBuilder::class)->build('hotel');
        $this->withSession(['nova.workspace' => $workspace]);

        $component = Livewire::test(NovaGraph::class);
        $derivedAction = collect($component->get('nodes'))->first(
            fn (array $node): bool => $node['type'] === 'action' && ! ($node['data']['meta']['custom'] ?? false),
        );
        $this->assertNotNull($derivedAction, 'Fixture expected to contain a derived action node.');

        $component->call('deleteNode', $derivedAction['id']);

        $nodeIds = array_column($component->get('nodes'), 'id');
        $this->assertContains($derivedAction['id'], $nodeIds);
    }

    public function test_deleting_a_resource_produced_only_by_an_improvement_cascades_to_that_capability(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        $workspace = app(WorkspaceBuilder::class)->build('hotel');
        $this->withSession(['nova.workspace' => $workspace]);

        $component = Livewire::test(NovaGraph::class)->call('addCapability', 'whatsapp');
        $this->assertContains('studio.graph.node.resource.mensaje', array_column($component->get('nodes'), 'id'));

        $component->call('deleteNode', 'studio.graph.node.resource.mensaje');

        $nodeIds = array_column($component->get('nodes'), 'id');
        $this->assertNotContains('studio.graph.node.resource.mensaje', $nodeIds);
        $this->assertNotContains('studio.graph.node.capability.whatsapp', $nodeIds);

        $updated = app(WorkspaceRegistry::class)->active();
        $this->assertNotContains('whatsapp', $updated['capability_ids']);
    }

    public function test_deleting_a_resource_produced_by_a_blueprint_default_area_is_a_no_op(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        $workspace = app(WorkspaceBuilder::class)->build('hotel');
        $this->withSession(['nova.workspace' => $workspace]);

        $component = Livewire::test(NovaGraph::class)
            ->call('deleteNode', 'studio.graph.node.resource.reserva');

        $nodeIds = array_column($component->get('nodes'), 'id');
        $this->assertContains('studio.graph.node.resource.reserva', $nodeIds);
        $this->assertContains('studio.graph.node.capability.reservations', $nodeIds);

        $updated = app(WorkspaceRegistry::class)->active();
        $this->assertContains('reservations', $updated['capability_ids']);
    }

    public function test_deleting_a_node_prunes_dangling_custom_relations(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        $workspace = app(WorkspaceBuilder::class)->build('hotel');
        $this->withSession(['nova.workspace' => $workspace]);

        Livewire::test(NovaGraph::class)->call('addCapability', 'whatsapp');

        Livewire::test(NovaGraph::class)->call(
            'addRelation',
            'studio.graph.node.capability.whatsapp',
            'studio.graph.node.resource.pago',
            'requiere',
        );

        Livewire::test(NovaGraph::class)->call('deleteNode', 'studio.graph.node.capability.whatsapp');

        $updated = app(WorkspaceRegistry::class)->active();
        $this->assertEmpty($updated['custom_relations'] ?? []);
    }

    public function test_deleting_the_selected_node_closes_the_inspector(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        $workspace = app(WorkspaceBuilder::class)->build('hotel');
        $this->withSession(['nova.workspace' => $workspace]);

        Livewire::test(NovaGraph::class)->call('addCapability', 'whatsapp');

        Livewire::test(NovaGraph::class)
            ->call('selectNode', 'studio.graph.node.capability.whatsapp')
            ->assertSet('selectedAddress', 'studio.graph.node.capability.whatsapp')
            ->call('deleteNode', 'studio.graph.node.capability.whatsapp')
            ->assertSet('selectedAddress', null);
    }

    public function test_add_relation_ignores_addresses_that_do_not_exist_in_the_graph(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        $workspace = app(WorkspaceBuilder::class)->build('hotel');
        $this->withSession(['nova.workspace' => $workspace]);

        $component = Livewire::test(NovaGraph::class)
            ->call('addRelation', 'studio.graph.node.capability.reservations', 'not-a-real-node', 'requiere');

        $workspace = app(WorkspaceRegistry::class)->active();
        $this->assertArrayNotHasKey('custom_relations', $workspace);
    }
}
