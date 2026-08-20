<?php

namespace Database\Factories;

use App\Models\FormaCobro;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormaCobro>
 */
class FormaCobroFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => strtoupper(fake()->unique()->bothify('FC-###')),
            'nombre' => fake()->randomElement(['Transferencia bancaria', 'Tarjeta de crédito', 'Domiciliación bancaria', 'Efectivo', 'PayPal', 'Financiación']),
            'descripcion' => fake()->optional()->sentence(),
            'activa' => fake()->boolean(90),
            'orden' => fake()->numberBetween(0, 100),
        ];
    }
}
