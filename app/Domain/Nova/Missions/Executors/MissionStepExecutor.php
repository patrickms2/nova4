<?php

declare(strict_types=1);

namespace App\Domain\Nova\Missions\Executors;

interface MissionStepExecutor
{
    /**
     * Determine whether this executor can run the given mission step.
     */
    public function supports(array $step, array $mission): bool;

    /**
     * Execute the step and return the updated mission.
     *
     * @param  array<string, mixed>  $step
     * @param  array<string, mixed>  $mission
     * @return array<string, mixed>
     */
    public function execute(array $step, array $mission): array;
}
