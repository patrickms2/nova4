<?php

declare(strict_types=1);

namespace App\Domain\Nova\Missions;

final class MissionProgress
{
    /** @param array<int, array<string, mixed>> $steps */
    public function calculate(array $steps): int
    {
        if ($steps === []) {
            return 0;
        }

        return (int) round(array_sum(array_column($steps, 'progress')) / count($steps));
    }
}
