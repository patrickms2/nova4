<?php

namespace Database\Factories;

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Models\Device;
use App\Models\Property;
use App\Models\RentalProperty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
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
            'name' => $this->faker->word().' '.$this->faker->randomNumber(3),
            'type' => $this->faker->randomElement(DeviceType::cases())->value,
            'identifier' => $this->faker->unique()->macAddress(),
            'status' => DeviceStatus::Unknown->value,
            'meta' => null,
        ];
    }
}
