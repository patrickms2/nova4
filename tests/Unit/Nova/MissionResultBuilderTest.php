<?php

declare(strict_types=1);

namespace Tests\Unit\Nova;

use App\Domain\Nova\Missions\MissionResultBuilder;
use Tests\TestCase;

class MissionResultBuilderTest extends TestCase
{
    public function test_it_translates_a_completed_mission_into_a_business_result(): void
    {
        $mission = [
            'id' => 'mission-1',
            'goal' => 'Preparar las facturas de este mes',
            'completed_at' => now()->toIso8601String(),
            'capabilities' => [
                ['id' => 'planning', 'name' => 'Planificación'],
                ['id' => 'payments', 'name' => 'Pagos'],
                ['id' => 'invoices', 'name' => 'Facturas'],
                ['id' => 'reporting', 'name' => 'Informes'],
            ],
            'artifacts' => [
                ['id' => 'file-1', 'name' => 'factura.pdf', 'type' => 'PDF', 'path' => 'missions/facturas/factura.pdf'],
            ],
        ];
        $navigation = [
            ['id' => 'home', 'name' => 'Inicio', 'icon' => '⌂'],
            ['id' => 'invoices', 'name' => 'Facturación', 'icon' => '🧾'],
            ['id' => 'reports', 'name' => 'Informes', 'icon' => '▥'],
        ];

        $result = app(MissionResultBuilder::class)->build($mission, $navigation)->toArray();

        $this->assertSame('invoices', $result['target_area_id']);
        $this->assertSame('Facturación', $result['target_area_name']);
        $this->assertContains('Las facturas están preparadas.', $result['outcomes']);
        $this->assertSame('Enviar las facturas a los clientes', $result['suggested_goal']);
        $this->assertCount(1, $result['files']);
    }
}
