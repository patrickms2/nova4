<?php

declare(strict_types=1);

namespace App\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\RentalType;

/**
 * @extends Factory<RentalType>
 */
class RentalTypeFactory extends Factory
{
    protected $model = RentalType::class;

    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug(2),
            'label' => $this->faker->words(2, true),
            'label_en' => null,
            'price' => $this->faker->numberBetween(5000, 50000),
            'currency' => 'PLN',
            'description' => $this->faker->optional()->sentence(),
            'description_en' => null,
            'available_from' => null,
            'available_until' => null,
            'is_active' => true,
            'sort_order' => 0,
            'meta' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
