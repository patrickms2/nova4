<?php

declare(strict_types=1);

namespace App\Domain\Nova\Capabilities;

final readonly class MissionResolver
{
    public function __construct(
        private BlueprintRegistry $blueprints,
        private CapabilityResolver $capabilities,
    ) {}

    /** @return array<string, mixed> */
    public function resolve(string $goal, string $businessType): array
    {
        $blueprint = $this->blueprints->get($businessType);
        $capabilities = $this->capabilities->resolve($goal, $blueprint);

        $capabilityNames = array_column($capabilities, 'name', 'id');

        return [
            'blueprint' => $blueprint->toArray(),
            'capabilities' => array_map(
                static fn (Capability $capability): array => $capability->toArray() + [
                    'dependencyNames' => array_map(
                        static fn (string $dependency): string => $capabilityNames[$dependency] ?? $dependency,
                        $capability->dependencies,
                    ),
                ],
                $capabilities,
            ),
            'agents' => $this->unique($capabilities, 'requiredAgents'),
            'connectors' => $this->unique($capabilities, 'requiredConnectors'),
            'providers' => $this->unique($capabilities, 'requiredProviders'),
            'artifacts' => $this->unique($capabilities, 'requiredArtifacts'),
            'estimated_duration' => array_sum(array_column($capabilities, 'estimatedDuration')),
            'requires_approval' => array_any(
                $capabilities,
                static fn (Capability $capability): bool => $capability->requiresApproval,
            ),
        ];
    }

    /**
     * @param  array<int, Capability>  $capabilities
     * @return array<int, string>
     */
    private function unique(array $capabilities, string $property): array
    {
        return array_values(array_unique(array_merge(
            ...array_map(
                static fn (Capability $capability): array => $capability->{$property},
                $capabilities,
            ),
        )));
    }
}
