<?php

namespace Database\Factories;

use App\Enums\AccessPointType;
use App\Models\AccessPoint;
use App\Models\Device;
use App\Models\Property;
use App\Models\RentalProperty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccessPoint>
 */
class AccessPointFactory extends Factory
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
            'device_id' => Device::factory(),
            'name' => $this->faker->randomElement(['Portón principal', 'Puerta de entrada', 'Garaje']),
            'type' => AccessPointType::Gate->value,
            'location' => $this->faker->optional()->city(),
            'is_active' => true,
        ];
    }
}
