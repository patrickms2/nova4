<?php

namespace Tests\Feature\Facturas;

use App\Livewire\Facturas\Clientes;
use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientesRecurrenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_carga_valores_actuales_de_recurrencia_al_abrir_modal(): void
    {
        $cliente = Cliente::factory()->create([
            'recurrencia_dia' => 15,
            'recurrencia_activa' => true,
            'recurrencia_notas' => 'Nota mensual',
        ]);

        Livewire::test(Clientes::class)
            ->call('openRecurrencia', $cliente->id)
            ->assertSet('recurrencia.cliente_id', $cliente->id)
            ->assertSet('recurrencia.dia', 15)
            ->assertSet('recurrencia.activa', true)
            ->assertSet('recurrencia.notas', 'Nota mensual');
    }

    public function test_guarda_configuracion_de_recurrencia_por_cliente(): void
    {
        $cliente = Cliente::factory()->create([
            'recurrencia_dia' => 1,
            'recurrencia_activa' => false,
            'recurrencia_notas' => null,
        ]);

        Livewire::test(Clientes::class)
            ->call('openRecurrencia', $cliente->id)
            ->set('recurrencia.dia', 20)
            ->set('recurrencia.activa', true)
            ->set('recurrencia.notas', 'Generar cada 20')
            ->call('saveRecurrencia');

        $cliente->refresh();

        $this->assertSame(20, $cliente->recurrencia_dia);
        $this->assertTrue($cliente->recurrencia_activa);
        $this->assertSame('Generar cada 20', $cliente->recurrencia_notas);
    }

    public function test_rechaza_dia_fuera_de_rango(): void
    {
        $cliente = Cliente::factory()->create();

        Livewire::test(Clientes::class)
            ->call('openRecurrencia', $cliente->id)
            ->set('recurrencia.dia', 35)
            ->call('saveRecurrencia')
            ->assertHasErrors(['recurrencia.dia']);
    }

    public function test_puede_cambiar_entre_vista_tarjetas_y_tabla(): void
    {
        $cliente = Cliente::factory()->create();

        Livewire::test(Clientes::class)
            ->assertSet('viewMode', 'cards')
            ->assertSee($cliente->nombrecorto)
            ->set('viewMode', 'table')
            ->assertSet('viewMode', 'table')
            ->assertSee('<table', false);
    }
}
