<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Nova\Studio\Workspace\WorkspaceBuilder;
use App\Domain\Nova\Studio\Workspace\WorkspaceRegistry;
use App\Livewire\Nova\NovaWorkspace;
use Illuminate\Auth\Middleware\Authenticate;
use Livewire\Livewire;
use Tests\TestCase;

class NovaWorkspaceTest extends TestCase
{
    public function test_workspace_entry_renders_the_one_time_evolution_experience(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        $this->withSession([
            'nova.workspace' => [
                'business_type' => 'hotel',
                'business_name' => 'Hotel',
                'business_icon' => '🏨',
                'capability_ids' => ['home', 'rooms', 'reservations', 'whatsapp'],
                'improvement_ids' => ['whatsapp'],
                'navigation' => [
                    ['id' => 'home', 'icon' => '⌂', 'name' => 'Inicio'],
                    ['id' => 'rooms', 'icon' => '🛏', 'name' => 'Habitaciones'],
                    ['id' => 'reservations', 'icon' => '📅', 'name' => 'Reservas'],
                ],
            ],
            'nova.workspace_transition' => [
                'active' => true,
                'evolved_area_id' => 'reservations',
                'new_improvement_id' => 'whatsapp',
            ],
        ]);

        $this->get(route('nova.nova-workspace'))
            ->assertOk()
            ->assertSee('Adaptando NOVA a tu negocio')
            ->assertSee('Todo listo. Ya puedes empezar a trabajar.')
            ->assertSee('¿Qué quieres conseguir hoy?')
            ->assertSee('Trabajo reciente')
            ->assertDontSee('NUEVA CAPACIDAD')
            ->assertDontSee('Instalar');
    }

    public function test_the_workspace_starts_with_a_goal_instead_of_internal_architecture(): void
    {
        Livewire::test(NovaWorkspace::class)
            ->assertOk()
            ->assertSee('¿Qué quieres conseguir hoy?')
            ->assertSee('Cuéntame tu objetivo')
            ->assertSee('Trabajo reciente')
            ->assertSee('Fuentes de datos')
            ->assertDontSee('Planificador')
            ->assertDontSee('Motor de misiones')
            ->assertDontSee('Conversation')
            ->assertSet('missions', [])
            ->assertSet('activeMission', null)
            ->assertSet('timeline', [])
            ->assertSet('events', []);
    }

    public function test_workspace_sections_expose_actionable_business_tools(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        $workspace = app(WorkspaceBuilder::class)->build('hotel');
        $this->withSession(['nova.workspace' => $workspace]);

        $this->get(route('nova.nova-workspace'))
            ->assertOk()
            ->assertSee('Crear reserva')
            ->assertSee('Reprogramar')
            ->assertSee('Actualizar disponibilidad');

        Livewire::test(NovaWorkspace::class)
            ->dispatch('nova-tool-selected', tool: 'Crear reserva', area: 'Reservas')
            ->assertSet('activeMission.goal', 'Crear reserva en Reservas')
            ->assertSet('activeMission.status', 'Detected')
            ->assertSee('Estoy trabajando en ello');
    }

    public function test_multiple_workspaces_are_selectable_from_the_sidebar_header(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        $registry = app(WorkspaceRegistry::class);
        $winery = $registry->save(app(WorkspaceBuilder::class)->build('winery'));
        $hotel = $registry->save(app(WorkspaceBuilder::class)->build('hotel'));

        $this->get(route('nova.nova-workspace'))
            ->assertOk()
            ->assertSee('nova-workspace-selector')
            ->assertSee('Bodega')
            ->assertSee('Hotel');

        $this->get(route('nova.nova-workspace', ['workspace' => $winery['id']]))
            ->assertOk()
            ->assertSee('Bodega')
            ->assertSessionHas('nova.active_workspace_id', $winery['id'])
            ->assertSessionHas('nova.workspace.business_name', 'Bodega');

        $this->assertNotSame($winery['id'], $hotel['id']);
    }

    public function test_a_professional_workspace_uses_its_variant_for_navigation_and_missions(): void
    {
        $workspace = app(WorkspaceBuilder::class)->build(
            'professional',
            [],
            null,
            'professional-appointments',
        );
        $this->withSession(['nova.workspace' => $workspace]);

        Livewire::test(NovaWorkspace::class)
            ->assertSet('business', 'professional-appointments')
            ->assertSet('businessName', 'Gestión de citas')
            ->assertSet('navigation', fn (array $navigation): bool => collect($navigation)->contains('id', 'departments')
                && collect($navigation)->contains('id', 'employees')
                && collect($navigation)->contains('id', 'appointments'))
            ->dispatch('nova-tool-selected', tool: 'Crear cita', area: 'Citas')
            ->assertSet('activeMission.blueprint.id', 'professional-appointments')
            ->assertSet('activeMission.capabilities', fn (array $capabilities): bool => collect($capabilities)->contains('id', 'professional-appointments'));
    }

    public function test_a_detected_mission_plans_and_starts_automatically(): void
    {
        Livewire::test(NovaWorkspace::class)
            ->set('prompt', 'Conectar WhatsApp con mis reservas')
            ->call('submitPrompt')
            ->assertSet('prompt', '')
            ->assertSet('activeMission.status', 'Detected')
            ->assertCount('activeMission.steps', 8)
            ->assertSet('activeMission.steps.0.tasks', fn (array $tasks): bool => count($tasks) >= 2)
            ->assertSet('activeMission.steps.0.title', 'Planificación')
            ->assertSee('Analizando el objetivo')
            ->assertSee('Entendido.')
            ->assertSee('Estoy trabajando en ello')
            ->assertSee('Coordinando el trabajo')
            ->assertSee('Ver cómo lo hizo NOVA')
            ->assertSeeHtml('<details id="mission-technical-details"')
            ->assertDontSeeHtml('<details open')
            ->call('advanceMission')
            ->assertSet('activeMission.status', 'Planning')
            ->call('advanceMission')
            ->assertSet('activeMission.status', 'Running')
            ->assertSet('activeMission.planner.locked', true);
    }

    public function test_a_sensitive_mission_waits_for_approval_before_execution(): void
    {
        Livewire::test(NovaWorkspace::class)
            ->set('prompt', 'Preparar las facturas de este mes')
            ->call('submitPrompt')
            ->call('advanceMission')
            ->call('advanceMission')
            ->assertSet('activeMission.status', 'Waiting Approval')
            ->assertSee('Solo necesito tu aprobación')
            ->assertSee('Aprobar y continuar')
            ->call('approveMission')
            ->assertSet('activeMission.status', 'Running');
    }

    public function test_runtime_generates_events_and_artifacts_during_execution(): void
    {
        $component = Livewire::test(NovaWorkspace::class)
            ->set('prompt', 'Conectar WhatsApp con mis reservas')
            ->call('submitPrompt');

        foreach (range(1, 7) as $tick) {
            $component->call('advanceMission');
        }

        $component
            ->assertSet('activeMission.status', 'Running')
            ->assertCount('activeMission.artifacts', 1);
    }

    public function test_demo_runtime_completes_with_live_progress(): void
    {
        $this->withSession([
            'nova.workspace' => app(WorkspaceBuilder::class)->build('winery'),
        ]);

        $component = Livewire::test(NovaWorkspace::class)
            ->set('prompt', 'Sincronizar mi catálogo de WooCommerce')
            ->call('submitPrompt');

        foreach (range(1, 19) as $tick) {
            $component->call('advanceMission');
        }

        $component
            ->assertSet('activeMission.status', 'Completed')
            ->assertSet('activeMission.progress', 100)
            ->assertCount('activeMission.artifacts', 3)
            ->assertSet('activeResult.target_area_id', 'products')
            ->assertCount('completedResults', 1)
            ->assertSee('Misión completada')
            ->assertSee('Lo que NOVA ha conseguido')
            ->assertSee('Tu Workspace se ha actualizado')
            ->assertSee('Continuar trabajando')
            ->assertSee('Siguiente paso sugerido')
            ->assertSee('Historial de trabajo')
            ->assertSee('Abrir Productos')
            ->assertSee('Crear otra misión')
            ->assertSee('Ver cómo NOVA completó esta misión')
            ->assertSee('Misión completada correctamente')
            ->assertSessionHas('nova.mission_history.bodega', fn (array $history): bool => count($history) === 1)
            ->assertSessionHas('nova.workspace_updates.bodega.products.count', 1)
            ->call('openResultArea', 'products')
            ->assertSet('activeMission', null)
            ->assertSet('activeWorkspaceArea.id', 'products')
            ->assertSee('Continúa trabajando exactamente donde NOVA lo dejó.')
            ->assertSee('+1 actualizado');
    }
}
