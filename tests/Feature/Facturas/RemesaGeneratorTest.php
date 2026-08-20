<?php

namespace Tests\Feature\Facturas;

use App\Models\Cliente;
use App\Models\Concepto;
use App\Models\Empresa;
use App\Models\Remesa;
use App\Services\Facturacion\RemesaGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemesaGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_genera_una_factura_por_cliente_con_conceptos_recurrentes(): void
    {
        $empresa = Empresa::factory()->create();
        $cliente = Cliente::factory()->create([
            'empresa_id' => $empresa->id,
            'recurrencia_activa' => true,
        ]);

        Concepto::create([
            'cliente_id' => $cliente->id,
            'codconcepto' => 'REC001',
            'concepto' => 'Mantenimiento mensual',
            'unidad' => 'mes',
            'precio' => 100.00,
            'descuento' => 0,
            'impuesto' => 7.00,
            'retenciones' => 15.00,
            'recurrente' => true,
        ]);

        $remesa = Remesa::factory()->create([
            'nombre' => 'Remesa julio',
            'fecha' => '2026-07-01',
        ]);
        $remesa->remesaClientes()->create(['cliente_id' => $cliente->id]);

        $result = app(RemesaGenerator::class)->generate($remesa);

        $this->assertSame(1, $result['created']);
        $this->assertSame(0, $result['skipped']);
        $this->assertEmpty($result['errors']);

        $remesa->refresh();
        $this->assertSame('generated', $remesa->estado);
        $this->assertCount(1, $remesa->facturas);

        $factura = $remesa->facturas->first();
        $this->assertNotNull($factura);
        $this->assertSame($cliente->id, $factura->cliente_id);
        $this->assertSame($remesa->id, $factura->remesa_id);
        $this->assertEquals(100.00, (float) $factura->baseimponible);
        $this->assertEquals(7.00, (float) $factura->impuesto);
        $this->assertEquals(15.00, (float) $factura->retenciones);
        $this->assertEquals(92.00, (float) $factura->importe);
        $this->assertCount(1, $factura->registros);
    }

    public function test_omite_clientes_sin_recurrencia_activa(): void
    {
        $empresa = Empresa::factory()->create();
        $cliente = Cliente::factory()->create([
            'empresa_id' => $empresa->id,
            'recurrencia_activa' => false,
        ]);

        $remesa = Remesa::factory()->create();
        $remesa->remesaClientes()->create(['cliente_id' => $cliente->id]);

        $result = app(RemesaGenerator::class)->generate($remesa);

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['skipped']);
        $this->assertEmpty($remesa->facturas);
    }

    public function test_omite_clientes_sin_conceptos_recurrentes(): void
    {
        $empresa = Empresa::factory()->create();
        $cliente = Cliente::factory()->create([
            'empresa_id' => $empresa->id,
            'recurrencia_activa' => true,
        ]);

        Concepto::create([
            'cliente_id' => $cliente->id,
            'codconcepto' => 'NO001',
            'concepto' => 'Concepto puntual',
            'precio' => 50.00,
            'recurrente' => false,
        ]);

        $remesa = Remesa::factory()->create();
        $remesa->remesaClientes()->create(['cliente_id' => $cliente->id]);

        $result = app(RemesaGenerator::class)->generate($remesa);

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['skipped']);
        $this->assertEmpty($remesa->facturas);
    }
}
