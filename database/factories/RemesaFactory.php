<?php

namespace Database\Factories;

use App\Models\Remesa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Remesa>
 */
class RemesaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Remesa '.$this->faker->monthName().' '.$this->faker->year(),
            'fecha' => $this->faker->date(),
            'estado' => 'draft',
            'notas' => $this->faker->optional()->sentence(),
        ];
    }

    public function generated(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'generated',
        ]);
    }
}
