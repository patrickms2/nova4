<?php

namespace Database\Factories;

use App\Models\Person;
use App\Models\RentalGuest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentalGuest>
 */
class RentalGuestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
        ];
    }
}
