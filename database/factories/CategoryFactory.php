<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'type' => fake()->randomElement(['income', 'expense', 'both']),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Category $category) {
            if (! $category->user_id) {
                $category->forceFill(['user_id' => User::factory()->create()->id])->save();
            }
        });
    }
}
