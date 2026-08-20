<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Factura;
use Illuminate\Database\Eloquent\Factories\Factory;

class FacturaFactory extends Factory
{
    protected $model = Factura::class;

    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'codcliente' => null,
            'cliente_nombre' => $this->faker->company(),
            'cliente_cif' => $this->faker->regexify('[A-Z]{1}[0-9]{8}'),
            'fechaemitido' => $this->faker->date(),
            'baseimponible' => 100,
            'baseexenta' => 0,
            'impuesto' => 7,
            'retenciones' => 15,
            'importe' => 92,
            'notas' => $this->faker->sentence(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Factura $factura): void {
            $factura->registros()->create([
                'concepto_id' => null,
                'descripcion' => 'Concepto de prueba',
                'cantidad' => 1,
                'unidad' => '1',
                'precio' => 100,
                'descuento' => 0,
                'impuesto' => 7,
                'retenciones' => 15,
                'valorimpuesto' => 7,
                'valorretenciones' => 15,
                'importe' => 92,
                'fecha' => $factura->fechaemitido,
            ]);
        });
    }
}
