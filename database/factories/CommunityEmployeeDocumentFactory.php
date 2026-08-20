<?php

namespace Database\Factories;

use App\Models\Community;
use App\Models\CommunityEmployeeDocument;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunityEmployeeDocument>
 */
class CommunityEmployeeDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'community_id' => Community::factory(),
            'employee_id' => Employee::factory(),
            'title' => fake()->sentence(3),
            'path' => 'comunigest/documents/'.fake()->uuid().'.pdf',
            'filename' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(10_000, 2_000_000),
            'status' => 'active',
        ];
    }
}
