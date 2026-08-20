<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\RentalProperty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentalProperty>
 */
class RentalPropertyFactory extends Factory
{
    protected $model = RentalProperty::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'code' => $this->faker->unique()->bothify('RP-####'),
            'name' => $this->faker->streetName(),
            'nickname' => $this->faker->optional()->word(),
            'address' => $this->faker->address(),
            'tourist_registry' => $this->faker->optional()->bothify('VV-####-###'),
            'cadastral_reference' => $this->faker->optional()->bothify('????##??###?##?#####?##'),
            'settings' => null,
            'financial_settings' => null,
            'is_active' => true,
        ];
    }
}
