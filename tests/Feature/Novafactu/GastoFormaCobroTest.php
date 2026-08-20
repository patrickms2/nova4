<?php

namespace Tests\Feature\Novafactu;

use App\Models\Gasto;
use App\Models\FormaCobro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GastoFormaCobroTest extends TestCase
{
    use RefreshDatabase;

    public function test_gasto_factory_creates_record(): void
    {
        $gasto = Gasto::factory()->create();

        $this->assertDatabaseHas('gastos', [
            'id' => $gasto->id,
            'descripcion' => $gasto->descripcion,
        ]);
    }

    public function test_forma_cobro_factory_creates_record(): void
    {
        $formaCobro = FormaCobro::factory()->create();

        $this->assertDatabaseHas('formas_cobro', [
            'id' => $formaCobro->id,
            'nombre' => $formaCobro->nombre,
        ]);
    }

    public function test_gasto_routes_are_registered(): void
    {
        $this->assertNotNull(route('filament.admin.facturacion.resources.gastos.index'));
    }

    public function test_forma_cobro_routes_are_registered(): void
    {
        $this->assertNotNull(route('filament.admin.facturacion.resources.forma-cobros.index'));
    }
}
