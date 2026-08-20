<?php

namespace Database\Factories;

use App\Models\Credential;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Credential>
 */
class CredentialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'type' => 'pin',
            'name' => 'PIN '.fake()->word(),
            'identifier' => fake()->unique()->uuid(),
            'secret' => (string) fake()->unique()->randomNumber(6, true),
            'status' => 'active',
            'valid_from' => now()->subHour(),
            'valid_until' => now()->addDay(),
            'metadata' => [],
        ];
    }
}
