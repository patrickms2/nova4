<?php

namespace Database\Factories;

use App\Models\WorkReport;
use App\Models\WorkSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkReport>
 */
class WorkReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'work_session_id' => WorkSession::factory(),
            'photos' => [],
        ];
    }
}
