<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Factura;
use App\Models\Remesa;
use App\Models\RemesaCliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RemesaCliente>
 */
class RemesaClienteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'remesa_id' => Remesa::factory(),
            'cliente_id' => Cliente::factory(),
            'factura_id' => null,
        ];
    }

    public function forRemesa(Remesa $remesa): static
    {
        return $this->state(fn () => [
            'remesa_id' => $remesa->id,
        ]);
    }

    public function forCliente(Cliente $cliente): static
    {
        return $this->state(fn () => [
            'cliente_id' => $cliente->id,
        ]);
    }

    public function withFactura(Factura $factura): static
    {
        return $this->state(fn () => [
            'factura_id' => $factura->id,
        ]);
    }
}
