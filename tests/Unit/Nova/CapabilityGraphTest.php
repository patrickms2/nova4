<?php

declare(strict_types=1);

namespace Tests\Unit\Nova;

use App\Domain\Nova\Capabilities\BlueprintRegistry;
use App\Domain\Nova\Capabilities\BusinessBlueprint;
use App\Domain\Nova\Capabilities\Capability;
use App\Domain\Nova\Capabilities\CapabilityGraph;
use App\Domain\Nova\Capabilities\CapabilityRegistry;
use App\Domain\Nova\Capabilities\MissionResolver;
use Tests\TestCase;

class CapabilityGraphTest extends TestCase
{
    public function test_same_reservation_mission_adapts_to_each_business_blueprint(): void
    {
        $resolver = app(MissionResolver::class);

        $hotel = $resolver->resolve('Create reservation', 'Hotel');
        $restaurant = $resolver->resolve('Create reservation', 'Restaurant');
        $winery = $resolver->resolve('Create reservation', 'Winery');

        $this->assertContains('Reserva de habitación', array_column($hotel['capabilities'], 'name'));
        $this->assertContains('Reserva de mesa', array_column($restaurant['capabilities'], 'name'));
        $this->assertContains('Reserva de visita a bodega', array_column($winery['capabilities'], 'name'));

        $this->assertContains('Conector de reservas de hotel', $hotel['connectors']);
        $this->assertContains('Conector de reservas de mesa', $restaurant['connectors']);
        $this->assertContains('Conector de reservas de visitas', $winery['connectors']);
    }

    public function test_graph_orders_dependencies_before_requested_capability(): void
    {
        $resolved = app(CapabilityGraph::class)->resolve(['winery-reservations']);
        $ids = array_column($resolved, 'id');

        $this->assertLessThan(
            array_search('winery-reservations', $ids, true),
            array_search('availability', $ids, true),
        );
        $this->assertLessThan(
            array_search('availability', $ids, true),
            array_search('calendar', $ids, true),
        );
    }

    public function test_professional_blueprints_resolve_their_own_business_skills(): void
    {
        $resolver = app(MissionResolver::class);

        $appointments = $resolver->resolve('Crear cita para mañana', 'professional-appointments');
        $documents = $resolver->resolve('Crear documento desde plantilla', 'professional-documents');
        $tickets = $resolver->resolve('Crear ticket urgente', 'professional-tickets');
        $tours = $resolver->resolve('Crear tour de bodega', 'professional-tours');

        $this->assertContains('Citas', array_column($appointments['capabilities'], 'name'));
        $this->assertContains('Documentos', array_column($documents['capabilities'], 'name'));
        $this->assertContains('Plantillas', array_column($documents['capabilities'], 'name'));
        $this->assertContains('Tickets', array_column($tickets['capabilities'], 'name'));
        $this->assertContains('Tours', array_column($tours['capabilities'], 'name'));
        $this->assertSame('Gestión de citas', $appointments['blueprint']['name']);
    }

    public function test_professional_blueprints_can_be_composed_for_one_workspace(): void
    {
        $resolved = app(MissionResolver::class)->resolve(
            'Crear una cita y preparar un documento',
            'professional-appointments+professional-documents',
        );
        $ids = array_column($resolved['capabilities'], 'id');

        $this->assertContains('professional-appointments', $ids);
        $this->assertContains('professional-documents', $ids);
        $this->assertContains('professional-templates', $ids);
        $this->assertSame(
            'professional-appointments+professional-documents',
            $resolved['blueprint']['id'],
        );
    }

    public function test_marketplace_can_register_capabilities_and_blueprints_without_code_installation(): void
    {
        $capabilities = app(CapabilityRegistry::class);
        $blueprints = app(BlueprintRegistry::class);

        $capabilities->register(new Capability(
            id: 'spa-bookings',
            name: 'Spa Booking',
            description: 'Books a spa treatment.',
            category: 'reservations',
            requiredAgents: ['Reservations'],
            requiredConnectors: ['Spa Connector'],
            requiredProviders: ['Spa Runtime'],
            requiredArtifacts: ['spa-booking.pdf'],
            dependencies: ['availability'],
            priority: 90,
            estimatedDuration: 5,
            supportedBusinessTypes: ['Spa'],
            status: 'active',
            intentTerms: ['booking'],
            icon: 'calendar',
            tool: 'spa.reserve',
        ));
        $blueprints->register(new BusinessBlueprint('spa', 'Spa', ['spa-bookings', 'reporting']));

        $this->assertSame('Spa Booking', $capabilities->get('spa-bookings')->name);
        $this->assertContains('spa-bookings', $blueprints->get('Spa')->capabilityIds);
    }

    public function test_planner_and_runtime_contain_no_business_specific_provider_rules(): void
    {
        $sources = file_get_contents(app_path('Domain/Nova/Missions/MissionRuntime.php'))
            .file_get_contents(app_path('Domain/Nova/Missions/MissionTimeline.php'));

        foreach (['WooCommerce', 'Stripe', 'LatePoint', 'WordPress'] as $provider) {
            $this->assertStringNotContainsString($provider, $sources);
        }
    }
}
