<?php

namespace Tests\Feature\Facturas;

use App\Livewire\Facturas\Facturas;
use App\Models\Factura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecalcularFacturasTest extends TestCase
{
    use RefreshDatabase;

    public function test_recalcula_importes_de_facturas_seleccionadas(): void
    {
        $factura = Factura::factory()->create([
            'baseimponible' => 0,
            'impuesto' => 0,
            'retenciones' => 0,
            'importe' => 0,
        ]);

        $factura->registros()->update([
            'valorimpuesto' => 0,
            'valorretenciones' => 0,
            'importe' => 0,
        ]);

        Livewire::test(Facturas::class)
            ->set('selectedFacturas', [$factura->id => true])
            ->call('recalcularSeleccionadas')
            ->assertDispatched('toast');

        $factura->refresh();
        $this->assertGreaterThan(0, $factura->baseimponible);
        $this->assertGreaterThan(0, $factura->importe);
        $this->assertGreaterThan(0, $factura->registros->first()->importe);
    }
}
