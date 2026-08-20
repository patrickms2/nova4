<?php

declare(strict_types=1);

namespace App\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use App\Models\AvailabilitySlot;

/**
 * @extends Factory<AvailabilitySlot>
 */
class AvailabilitySlotFactory extends Factory
{
    protected $model = AvailabilitySlot::class;

    public function definition(): array
    {
        $start = Carbon::today()->addDays($this->faker->numberBetween(1, 30));
        $end = (clone $start)->addDays($this->faker->numberBetween(0, 5));

        return [
            'rentable_type' => 'App\\Models\\Motorcycle',
            'rentable_id' => $this->faker->numberBetween(1, 10),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'is_blocked' => true,
            'available_pers' => $this->faker->numberBetween(1, 4),
            'price' => $this->faker->randomFloat(2, 100, 1000),
            'reason' => $this->faker->randomElement(['Serwis', 'Wynajem prywatny', 'Niedostepne']),
        ];
    }

    public function forRentable(string $type, int|string $id): static
    {
        return $this->state(fn () => [
            'rentable_type' => $type,
            'rentable_id' => $id,
        ]);
    }

    public function range(string $start, string $end): static
    {
        return $this->state(fn () => [
            'start_date' => $start,
            'end_date' => $end,
        ]);
    }
}
