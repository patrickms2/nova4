<?php

declare(strict_types=1);

namespace App\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use App\Models\Rental;

/**
 * @extends Factory<Rental>
 */
class RentalFactory extends Factory
{
    protected $model = Rental::class;

    public function definition(): array
    {
        $start = Carbon::today()->addDays($this->faker->numberBetween(1, 30));
        $end = (clone $start)->addDays($this->faker->numberBetween(0, 7));
        $qty = $this->faker->numberBetween(1, 3);
        $unitPrice = $this->faker->numberBetween(10000, 50000);

        return [
            'rentable_type' => 'App\\Models\\Motorcycle',
            'rentable_id' => $this->faker->numberBetween(1, 10),
            'rental_type_id' => null,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'qty' => $qty,
            'message' => null,
            'gdpr_consent' => true,
            'total_amount' => $unitPrice * $qty,
            'currency' => 'PLN',
            'locale' => 'pl',
            'status' => 'pending',
            'payment_session_id' => null,
            'payment_order_id' => null,
            'meta' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['status' => 'confirmed']);
    }

    public function paid(): static
    {
        return $this->state(fn () => ['status' => 'paid']);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => 'cancelled']);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['status' => 'expired']);
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
