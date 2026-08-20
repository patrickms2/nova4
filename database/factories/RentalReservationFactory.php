<?php

namespace Database\Factories;

use App\Models\Person;
use App\Models\Property;
use App\Models\RentalGuest;
use App\Models\RentalProperty;
use App\Models\RentalReservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentalReservation>
 */
class RentalReservationFactory extends Factory
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
            'person_id' => Person::factory(),
            'guest_id' => RentalGuest::factory(),
            'channel' => 'direct',
            'reference_code' => fake()->unique()->bothify('RES-####'),
            'check_in' => today()->addDay(),
            'check_out' => today()->addDays(4),
            'adults' => 2,
            'children' => 0,
            'amount' => 500,
            'status' => 'confirmed',
        ];
    }
}
