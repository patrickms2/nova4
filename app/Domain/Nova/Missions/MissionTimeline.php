<?php

declare(strict_types=1);

namespace App\Domain\Nova\Missions;

use App\Domain\Nova\Capabilities\Capability;

final class MissionTimeline
{
    /**
     * @param  array<int, array<string, mixed>>  $resolvedCapabilities
     * @return array<int, array<string, mixed>>
     */
    public function fromCapabilities(array $resolvedCapabilities): array
    {
        return array_map(static function (array $capability): array {
            unset($capability['dependencyNames']);

            return MissionStep::fromCapability(
                new Capability(...$capability),
            )->toArray();
        }, $resolvedCapabilities);
    }
}
