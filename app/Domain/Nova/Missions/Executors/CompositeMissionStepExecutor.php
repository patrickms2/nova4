<?php

declare(strict_types=1);

namespace App\Domain\Nova\Missions\Executors;

final readonly class CompositeMissionStepExecutor implements MissionStepExecutor
{
    /** @param  array<int, MissionStepExecutor>  $executors */
    public function __construct(private array $executors) {}

    public function supports(array $step, array $mission): bool
    {
        foreach ($this->executors as $executor) {
            if ($executor->supports($step, $mission)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    public function execute(array $step, array $mission): array
    {
        foreach ($this->executors as $executor) {
            if ($executor->supports($step, $mission)) {
                return $executor->execute($step, $mission);
            }
        }

        return $mission;
    }
}
