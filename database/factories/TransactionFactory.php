<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'description' => fake()->sentence(3),
            'amount' => fake()->randomFloat(2, 1, 1000),
            'type' => 'expense',
            'category_id' => null,
            'date' => fake()->date(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Transaction $transaction) {
            if (! $transaction->user_id) {
                $transaction->forceFill(['user_id' => User::factory()->create()->id])->save();
            }
        });
    }
}
