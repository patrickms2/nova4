<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Gasto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gasto>
 */
class GastoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $base = fake()->randomFloat(2, 10, 500);
        $igic = round($base * 0.07, 2);

        return [
            'empresa_id' => Empresa::inRandomOrder()->value('id'),
            'proveedor_id' => Cliente::inRandomOrder()->value('id'),
            'codigo' => 'GAS-'.fake()->unique()->numberBetween(1000, 9999).'-'.now()->year,
            'descripcion' => fake()->sentence(3),
            'notas' => fake()->optional()->sentence(),
            'categoria' => fake()->randomKey(Gasto::categorias()),
            'fecha' => fake()->dateTimeBetween('-1 year', 'now'),
            'base_imponible' => $base,
            'impuesto' => $igic,
            'total' => $base + $igic,
            'estado' => fake()->randomKey(Gasto::estados()),
            'metodo_pago' => fake()->randomElement(['Transferencia', 'Tarjeta', 'Domiciliado', 'Efectivo']),
            'documento' => fake()->optional()->filePath(),
            'deducible' => fake()->boolean(80),
        ];
    }
}
