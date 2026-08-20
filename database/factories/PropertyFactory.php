<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->streetName(),
            'slug' => $this->faker->unique()->slug(2),
            'address' => $this->faker->address(),
            'timezone' => 'Atlantic/Canary',
            'owner_id' => User::factory(),
            'is_active' => true,
        ];
    }
}
