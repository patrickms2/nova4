<?php

namespace Tests\Feature\Facturas;

use App\Models\Cliente;
use App\Models\Factura;
use App\Models\RegistroFactura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacturaRectificativaTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_rectificativa_with_negative_amounts(): void
    {
        $cliente = Cliente::factory()->create();
        $factura = new Factura;
        $factura->cliente_id = $cliente->id;
        $factura->codcliente = $cliente->id;
        $factura->baseimponible = 100;
        $factura->impuesto = 7;
        $factura->retenciones = 15;
        $factura->importe = 92;
        $factura->fechaemitido = now();
        $factura->save();

        RegistroFactura::create([
            'factura_id' => $factura->id,
            'concepto_id' => null,
            'descripcion' => 'Servicio',
            'cantidad' => 1,
            'precio' => 100,
            'descuento' => 0,
            'valorimpuesto' => 0,
            'valorretenciones' => 0,
            'importe' => 100,
            'fecha' => now(),
        ]);

        $rectificativa = $factura->createRectificativa();

        $this->assertTrue($rectificativa->rectificativa);
        $this->assertEquals($factura->id, $rectificativa->factura_original_id);
        $this->assertEquals(-100, $rectificativa->baseimponible);
        $this->assertEquals(-7, $rectificativa->impuesto);
        $this->assertEquals(-15, $rectificativa->retenciones);
        $this->assertEquals(-92, $rectificativa->importe);
        $this->assertEquals(1, $rectificativa->registros()->count());
        $this->assertEquals(-100, $rectificativa->registros()->first()->importe);
        $this->assertNotEquals($factura->codfactura, $rectificativa->codfactura);
    }
}
