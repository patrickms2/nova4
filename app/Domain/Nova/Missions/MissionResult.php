<?php

declare(strict_types=1);

namespace App\Domain\Nova\Missions;

final readonly class MissionResult
{
    /**
     * @param  array<int, string>  $outcomes
     * @param  array<int, string>  $impact
     * @param  array<int, array<string, string>>  $files
     */
    public function __construct(
        public string $missionId,
        public string $goal,
        public string $summary,
        public string $targetAreaId,
        public string $targetAreaName,
        public string $targetAreaIcon,
        public array $outcomes,
        public array $impact,
        public array $files,
        public string $suggestedGoal,
        public string $completedAt,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'mission_id' => $this->missionId,
            'goal' => $this->goal,
            'summary' => $this->summary,
            'target_area_id' => $this->targetAreaId,
            'target_area_name' => $this->targetAreaName,
            'target_area_icon' => $this->targetAreaIcon,
            'outcomes' => $this->outcomes,
            'impact' => $this->impact,
            'files' => $this->files,
            'suggested_goal' => $this->suggestedGoal,
            'completed_at' => $this->completedAt,
        ];
    }
}
