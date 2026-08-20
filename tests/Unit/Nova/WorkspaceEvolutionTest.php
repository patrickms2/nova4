<?php

declare(strict_types=1);

namespace Tests\Unit\Nova;

use App\Domain\Nova\Studio\Workspace\WorkspaceBuilder;
use App\Domain\Nova\Studio\Workspace\WorkspaceEvolution;
use Tests\TestCase;

class WorkspaceEvolutionTest extends TestCase
{
    public function test_an_improvement_expands_an_existing_area_without_adding_navigation(): void
    {
        $workspace = app(WorkspaceBuilder::class)->build('winery');
        $before = array_column($workspace['navigation'], 'id');

        $workspace = app(WorkspaceEvolution::class)->improve($workspace, 'whatsapp');
        $reservations = collect($workspace['navigation'])->firstWhere('id', 'reservations');

        $this->assertSame($before, array_column($workspace['navigation'], 'id'));
        $this->assertContains('Confirmar por WhatsApp', $reservations['tools']);
        $this->assertNotContains('whatsapp', array_column($workspace['navigation'], 'id'));
    }

    public function test_store_orders_can_grow_with_shipping_tracking(): void
    {
        $workspace = app(WorkspaceBuilder::class)->build('store');
        $recommendations = app(WorkspaceEvolution::class)->recommendations($workspace);

        $this->assertContains('shipping', array_column($recommendations, 'id'));

        $workspace = app(WorkspaceEvolution::class)->improve($workspace, 'shipping');
        $orders = collect($workspace['navigation'])->firstWhere('id', 'orders');

        $this->assertContains('Marcar como enviado', $orders['tools']);
        $this->assertContains('Marcar como entregado', $orders['tools']);
    }

    public function test_each_professional_variant_generates_its_own_business_areas(): void
    {
        $builder = app(WorkspaceBuilder::class);

        $restaurantSales = $builder->build('professional', [], null, 'professional-restaurant-sales');
        $storeSales = $builder->build('professional', [], null, 'professional-store-sales');
        $winerySales = $builder->build('professional', [], null, 'professional-winery-sales');
        $restaurantBookings = $builder->build('professional', [], null, 'professional-restaurant-reservations');
        $documents = $builder->build('professional', [], null, 'professional-documents');
        $tickets = $builder->build('professional', [], null, 'professional-tickets');

        $this->assertContains('restaurant-menu', array_column($restaurantSales['navigation'], 'id'));
        $this->assertContains('store-catalog', array_column($storeSales['navigation'], 'id'));
        $this->assertContains('winery-catalog', array_column($winerySales['navigation'], 'id'));
        $this->assertContains('dining-rooms', array_column($restaurantBookings['navigation'], 'id'));
        $this->assertContains('professional-documents', array_column($documents['navigation'], 'id'));
        $this->assertContains('support-tickets', array_column($tickets['navigation'], 'id'));
        $this->assertSame('professional-tickets', $tickets['blueprint_id']);
    }

    public function test_professional_variants_merge_navigation_without_duplicates(): void
    {
        $workspace = app(WorkspaceBuilder::class)->build(
            'professional',
            [],
            null,
            ['professional-appointments', 'professional-documents'],
        );
        $areaIds = array_column($workspace['navigation'], 'id');

        $this->assertContains('appointments', $areaIds);
        $this->assertContains('professional-documents', $areaIds);
        $this->assertContains('templates', $areaIds);
        $this->assertSame($areaIds, array_values(array_unique($areaIds)));
        $this->assertSame(
            'professional-appointments+professional-documents',
            $workspace['blueprint_id'],
        );
    }

    public function test_remove_capability_strips_the_id_from_capability_and_improvement_ids(): void
    {
        $workspace = app(WorkspaceBuilder::class)->build('winery');
        $workspace = app(WorkspaceEvolution::class)->improve($workspace, 'whatsapp');
        $this->assertContains('whatsapp', $workspace['improvement_ids']);
        $this->assertContains('whatsapp', $workspace['capability_ids']);

        $workspace = app(WorkspaceEvolution::class)->removeCapability($workspace, 'whatsapp');

        $this->assertNotContains('whatsapp', $workspace['improvement_ids']);
        $this->assertNotContains('whatsapp', $workspace['capability_ids']);
    }

    public function test_remove_capability_is_a_no_op_for_an_unknown_id(): void
    {
        $workspace = app(WorkspaceBuilder::class)->build('winery');
        $before = $workspace['capability_ids'];

        $workspace = app(WorkspaceEvolution::class)->removeCapability($workspace, 'not-a-real-id');

        $this->assertSame($before, $workspace['capability_ids']);
    }

    public function test_legacy_professional_variant_is_migrated_to_the_multiple_format(): void
    {
        $workspace = app(WorkspaceBuilder::class)->build(
            'professional',
            [],
            null,
            'professional-documents',
        );
        unset($workspace['professional_variants']);

        $workspace = app(WorkspaceEvolution::class)->normalize($workspace);

        $this->assertSame(['professional-documents'], $workspace['professional_variants']);
        $this->assertSame('professional-documents', $workspace['professional_variant']);
        $this->assertContains('professional-documents', array_column($workspace['navigation'], 'id'));
    }
}
