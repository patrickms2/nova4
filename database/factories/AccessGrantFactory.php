<?php

namespace Database\Factories;

use App\Models\AccessGrant;
use App\Models\Property;
use App\Models\RentalProperty;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccessGrant>
 */
class AccessGrantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'rental_property_id' => RentalProperty::factory(),
            'user_id' => User::factory(),
            'name' => $this->faker->lastName().' - '.$this->faker->monthName(),
            'pin' => (string) $this->faker->unique()->randomNumber(4, true),
            'valid_from' => null,
            'valid_until' => null,
            'is_active' => true,
        ];
    }
}
