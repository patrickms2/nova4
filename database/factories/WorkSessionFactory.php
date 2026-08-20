<?php

namespace Database\Factories;

use App\Enums\WorkSessionStatus;
use App\Models\AccessGrant;
use App\Models\Property;
use App\Models\User;
use App\Models\WorkSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkSession>
 */
class WorkSessionFactory extends Factory
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
            'access_grant_id' => AccessGrant::factory(),
            'user_id' => User::factory(),
            'status' => WorkSessionStatus::Active->value,
            'started_at' => now(),
        ];
    }
}
