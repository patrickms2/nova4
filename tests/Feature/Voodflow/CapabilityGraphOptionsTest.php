<?php

namespace Tests\Feature\Voodflow;

use App\Domain\Nova\Studio\Workspace\Graph\CapabilityNodeOptions;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test for mission 030's Capability "+" contextual menu resolver.
 *
 * CapabilityNodeOptions must read from the same canonical NOVA source
 * GraphBuilder uses (WorkspaceModel::entityFor()/processFor()), never
 * duplicate that knowledge. See .codex/tasks/030-VoodFlow interaction
 * milestone.md, requirement 3.
 */
class CapabilityGraphOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_the_action_and_resource_for_a_known_capability(): void
    {
        $resolver = app(CapabilityNodeOptions::class);

        $options = $resolver->forCapability('reservations');

        $this->assertSame('reservations', $options['capability']);
        $this->assertSame([
            'address' => 'studio.graph.node.action.recibir-y-gestionar-reservas',
            'label' => 'Recibir y gestionar reservas',
        ], $options['actions'][0]);
        $this->assertSame([
            'address' => 'studio.graph.node.resource.reserva',
            'label' => 'Reserva',
        ], $options['resources'][0]);
    }

    public function test_it_returns_empty_lists_for_a_capability_with_no_mapped_entity_or_process(): void
    {
        $resolver = app(CapabilityNodeOptions::class);

        $options = $resolver->forCapability('home');

        $this->assertSame([], $options['actions']);
        $this->assertSame([], $options['resources']);
    }

    public function test_it_extracts_the_capability_id_from_the_stored_description(): void
    {
        $this->assertSame(
            'reservations',
            CapabilityNodeOptions::capabilityIdFromDescription('NOVA Address: studio.graph.node.capability.reservations'),
        );

        $this->assertNull(CapabilityNodeOptions::capabilityIdFromDescription('NOVA Address: studio.graph.node.resource.reserva'));
        $this->assertNull(CapabilityNodeOptions::capabilityIdFromDescription(null));
    }

    public function test_the_http_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/nova/graph/capability-options?capability=reservations');

        $response->assertUnauthorized();
    }

    public function test_the_http_endpoint_returns_options_for_an_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/nova/graph/capability-options?capability=payments');

        $response->assertOk();
        $response->assertJsonPath('capability', 'payments');
        $response->assertJsonPath('actions.0.label', 'Registrar cobros');
        $response->assertJsonPath('resources.0.label', 'Pago');
    }

    public function test_the_http_endpoint_requires_a_capability_query_parameter(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/nova/graph/capability-options');

        $response->assertStatus(422);
    }
}
