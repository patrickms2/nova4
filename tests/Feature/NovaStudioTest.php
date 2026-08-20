<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Nova\Studio\Workspace\WorkspaceBuilder;
use App\Domain\Nova\Studio\Workspace\WorkspaceRegistry;
use App\Livewire\Nova\NovaStudio;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

class NovaStudioTest extends TestCase
{
    public function test_studio_route_and_welcome_screen_are_available(): void
    {
        $this->assertTrue(Route::has('nova.studio'));

        Livewire::test(NovaStudio::class)
            ->assertOk()
            ->assertSee('Vamos a crear el Workspace perfecto para tu negocio')
            ->assertSee('Empezar')
            ->assertDontSee('WordPress')
            ->assertDontSee('OAuth');
    }

    public function test_studio_advances_through_business_discovery_and_proposal(): void
    {
        $studio = Livewire::test(NovaStudio::class)
            ->call('start')
            ->assertSet('step', 'activity')
            ->call('toggleActivity', 'sales')
            ->call('toggleActivity', 'services')
            ->assertSet('step', 'business')
            ->call('selectBusiness', 'winery')
            ->assertSet('step', 'objectives')
            ->call('toggleObjective', 'sell-online')
            ->call('toggleObjective', 'accept-reservations')
            ->call('confirmObjectives')
            ->assertSet('step', 'tools')
            ->call('toggleTool', 'whatsapp')
            ->call('confirmTools')
            ->assertSet('step', 'website')
            ->call('chooseWebsite', false)
            ->assertSet('step', 'confirmation');

        $studio
            ->assertSet('workspacePreview.business_name', 'Bodega')
            ->assertSet('workspacePreview.operational_model.entities', fn (array $entities): bool => count($entities) > 0)
            ->assertSee('Confirmo cómo entiendo tu negocio')
            ->assertSee('Quiero vender online')
            ->assertSee('WhatsApp');
    }

    public function test_can_create_workspace_for_vacation_rental(): void
    {
        $studio = Livewire::test(NovaStudio::class)
            ->call('start')
            ->call('toggleActivity', 'services')
            ->call('selectBusiness', 'vacation-rental')
            ->call('toggleObjective', 'accept-reservations')
            ->call('confirmObjectives')
            ->call('confirmTools')
            ->call('chooseWebsite', false)
            ->call('chooseImport', 'skip')
            ->call('createWorkspace');

        foreach (range(1, 6) as $tick) {
            $studio->call('advanceSequence');
        }

        $studio->assertSet('step', 'overview');
    }

    public function test_professionals_can_combine_appointments_and_documents(): void
    {
        $studio = Livewire::test(NovaStudio::class)
            ->call('start')
            ->call('toggleActivity', 'sales')
            ->call('toggleActivity', 'services')
            ->call('selectBusiness', 'professional')
            ->assertSet('step', 'professional-activity')
            ->assertSee('Venta')
            ->assertSee('Servicios')
            ->call('selectProfessionalActivity', 'services')
            ->assertSet('step', 'professional-variant')
            ->assertSee('Gestión de tours')
            ->assertSee('Gestión de citas')
            ->assertSee('Gestión de documentos')
            ->assertSee('Gestión de tickets')
            ->call('selectProfessionalVariant', 'professional-appointments')
            ->assertSet('step', 'professional-variant')
            ->call('selectProfessionalVariant', 'professional-documents')
            ->assertSet('professionalVariants', [
                'professional-appointments',
                'professional-documents',
            ])
            ->call('confirmProfessionalVariants')
            ->assertSet('step', 'objectives')
            ->call('toggleObjective', 'manage-documents')
            ->call('confirmObjectives')
            ->assertSet('step', 'tools')
            ->call('confirmTools')
            ->assertSet('step', 'website')
            ->call('chooseWebsite', false)
            ->assertSet('step', 'confirmation');

        $studio
            ->assertSet('workspacePreview.business_type', 'professional')
            ->assertSet('workspacePreview.professional_activity', 'services')
            ->assertSet('workspacePreview.professional_variant', 'professional-appointments')
            ->assertSet('workspacePreview.professional_variants', [
                'professional-appointments',
                'professional-documents',
            ])
            ->assertSet(
                'workspacePreview.blueprint_id',
                'professional-appointments+professional-documents',
            )
            ->assertSet('workspacePreview.navigation', fn (array $navigation): bool => collect($navigation)->contains('id', 'company')
                && collect($navigation)->contains('id', 'departments')
                && collect($navigation)->contains('id', 'employees')
                && collect($navigation)->contains('id', 'slots')
                && collect($navigation)->contains('id', 'appointments')
                && collect($navigation)->contains('id', 'templates')
                && collect($navigation)->contains('id', 'professional-documents'));
    }

    public function test_completed_build_lands_on_permanent_studio_home(): void
    {
        $studio = Livewire::test(NovaStudio::class)
            ->call('start')
            ->call('toggleActivity', 'sales')
            ->call('toggleActivity', 'services')
            ->call('selectBusiness', 'hotel')
            ->call('toggleObjective', 'accept-reservations')
            ->call('toggleObjective', 'automate-whatsapp')
            ->call('confirmObjectives')
            ->call('confirmTools')
            ->call('chooseWebsite', false)
            ->call('createWorkspace')
            ->assertSet('step', 'building')
            ->assertSee('Actualizando tu Workspace');

        foreach (range(1, 6) as $tick) {
            $studio->call('advanceSequence');
        }

        $studio
            ->assertSet('step', 'overview')
            ->assertSee('Representaciones del Workspace')
            ->assertSee('Así trabaja NOVA contigo hoy.')
            ->assertSee('Mejoras recomendadas')
            ->assertSee('Gestión del Workspace')
            ->assertSee('Nuevo Workspace')
            ->assertSessionHas('nova.workspace.business_name', 'Hotel')
            ->assertSessionHas('nova.workspaces', fn (array $workspaces): bool => count($workspaces) === 1)
            ->assertSessionHas('nova.workspace.operational_model', fn (array $model): bool => count($model['entities']) > 0)
            ->assertSessionHas('nova.workspace.navigation', fn (array $navigation): bool => collect($navigation)->contains(
                fn (array $area): bool => $area['id'] === 'reservations',
            ) && ! collect($navigation)->contains('id', 'whatsapp'));
    }

    public function test_existing_workspace_opens_as_studio_home(): void
    {
        $this->withSession([
            'nova.workspace' => app(WorkspaceBuilder::class)->build('winery'),
        ]);

        Livewire::test(NovaStudio::class)
            ->assertSet('step', 'overview')
            ->assertSee('Tu Workspace')
            ->assertSee('Mejoras recomendadas')
            ->assertSee('Muchas bodegas confirman sus reservas automáticamente por WhatsApp.')
            ->assertSee('Añadir a Reservas')
            ->assertSee('Representaciones del Workspace')
            ->assertDontSee('Marketplace')
            ->assertDontSee('Instalar');
    }

    public function test_preview_is_immersive_and_returns_to_studio(): void
    {
        $this->withSession([
            'nova.workspace' => app(WorkspaceBuilder::class)->build('winery'),
        ]);

        Livewire::test(NovaStudio::class)
            ->call('openRepresentations')
            ->assertSet('step', 'representations')
            ->assertSee('Representaciones')
            ->assertSee('El mismo modelo operativo en distintos canales.')
            ->assertSee('Volver a Studio')
            ->assertSee('Abrir Workspace')
            ->call('returnToOverview')
            ->assertSet('step', 'overview');
    }

    public function test_recommended_improvement_updates_the_live_preview(): void
    {
        $this->withSession([
            'nova.workspace' => app(WorkspaceBuilder::class)->build('winery'),
        ]);

        $studio = Livewire::test(NovaStudio::class)
            ->call('improveWorkspace', 'whatsapp')
            ->assertSet('step', 'evolving')
            ->assertSee('Estoy mejorando Reservas');

        foreach (range(1, 4) as $tick) {
            $studio->call('advanceSequence');
        }

        $studio
            ->assertSet('step', 'representations')
            ->assertSet('evolvedAreaId', 'reservations')
            ->assertSet('newImprovementId', 'whatsapp')
            ->assertSee('Reservas ahora incluye WhatsApp.')
            ->assertSee('Confirmar por WhatsApp')
            ->assertSessionHas('nova.workspace.improvement_ids', ['whatsapp']);
    }

    public function test_editing_is_preloaded_and_preserves_workspace_identity(): void
    {
        $workspace = app(WorkspaceBuilder::class)->build('winery', ['whatsapp']);
        $this->withSession(['nova.workspace' => $workspace]);

        Livewire::test(NovaStudio::class)
            ->call('editWorkspace')
            ->assertSet('step', 'edit')
            ->assertSet('businessType', 'winery')
            ->assertSet('extraImprovements', ['whatsapp'])
            ->assertSet('workspaceName', 'Bodega')
            ->set('workspaceName', 'La Geria')
            ->call('saveWorkspaceEdits')
            ->assertSet('step', 'representations')
            ->assertSet('workspacePreview.id', $workspace['id'])
            ->assertSet('workspacePreview.business_name', 'La Geria')
            ->assertSessionHas('nova.workspace.business_name', 'La Geria');
    }

    public function test_editing_a_professional_workspace_can_add_appointments_to_documents(): void
    {
        $workspace = app(WorkspaceBuilder::class)->build(
            'professional',
            [],
            null,
            'professional-documents',
        );
        $workspace['business_name'] = 'Abogado';
        $this->withSession(['nova.workspace' => $workspace]);

        Livewire::test(NovaStudio::class)
            ->call('editWorkspace')
            ->assertSet('professionalVariants', ['professional-documents'])
            ->call('selectEditedProfessionalVariant', 'professional-appointments')
            ->assertSet('professionalVariants', [
                'professional-documents',
                'professional-appointments',
            ])
            ->assertSet('workspacePreview.navigation', fn (array $navigation): bool => collect($navigation)->contains('id', 'appointments')
                && collect($navigation)->contains('id', 'professional-documents'))
            ->call('saveWorkspaceEdits')
            ->assertSet('step', 'representations')
            ->assertSessionHas('nova.workspace.professional_variants', [
                'professional-documents',
                'professional-appointments',
            ])
            ->assertSessionHas(
                'nova.workspace.blueprint_id',
                'professional-documents+professional-appointments',
            );
    }

    public function test_multiple_workspaces_can_be_kept_and_activated_independently(): void
    {
        $registry = app(WorkspaceRegistry::class);
        $winery = $registry->save(app(WorkspaceBuilder::class)->build('winery'));
        $hotel = $registry->save(app(WorkspaceBuilder::class)->build('hotel'));

        Livewire::test(NovaStudio::class)
            ->assertSet('workspacePreview.id', $hotel['id'])
            ->assertSet('workspaces', fn (array $workspaces): bool => count($workspaces) === 2)
            ->assertSee('Bodega')
            ->assertSee('Hotel')
            ->call('switchWorkspace', $winery['id'])
            ->assertSet('workspacePreview.id', $winery['id'])
            ->assertSessionHas('nova.active_workspace_id', $winery['id'])
            ->call('startNewWorkspace')
            ->assertSet('step', 'activity')
            ->assertSessionHas('nova.workspaces', fn (array $workspaces): bool => count($workspaces) === 2);
    }

    public function test_website_discovery_uses_only_business_language(): void
    {
        Livewire::test(NovaStudio::class)
            ->set('step', 'url')
            ->set('businessType', 'winery')
            ->set('websiteUrl', 'https://lageria.com')
            ->call('discoverWebsite')
            ->assertSet('step', 'discovery')
            ->assertSee('Estoy descubriendo tu negocio')
            ->assertDontSee('WooCommerce')
            ->assertDontSee('API');
    }

    public function test_website_discovery_executes_a_real_mission_and_extracts_facts(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'https://lageria.com' => \Illuminate\Support\Facades\Http::response(
                '<html lang="es"><head><title>Lageria - Bodega</title><meta name="description" content="Vinos y visitas"></head><body></body></html>',
                200,
            ),
        ]);

        $studio = Livewire::test(NovaStudio::class)
            ->call('start')
            ->call('toggleActivity', 'sales')
            ->call('toggleActivity', 'services')
            ->call('selectBusiness', 'winery')
            ->call('toggleObjective', 'sell-online')
            ->call('confirmObjectives')
            ->call('confirmTools')
            ->call('chooseWebsite', true)
            ->set('websiteUrl', 'https://lageria.com')
            ->call('discoverWebsite')
            ->assertSet('step', 'discovery')
            ->assertSet('discoveryMission', fn (?array $mission): bool => $mission !== null && $mission['goal'] === 'Descubrir negocio desde la web');

        foreach (range(1, 15) as $tick) {
            $studio->call('advanceSequence');
        }

        $studio
            ->assertSet('step', 'import')
            ->assertSet('websiteUrl', 'https://lageria.com')
            ->assertSet('discoveryMission.status', 'Completed')
            ->call('chooseImport', 'skip')
            ->assertSet('workspacePreview.discovered_facts.title', 'Lageria - Bodega')
            ->assertSet('workspacePreview.discovered_facts.description', 'Vinos y visitas')
            ->assertSet('workspacePreview.discovered_facts.language', 'es');
    }
}
