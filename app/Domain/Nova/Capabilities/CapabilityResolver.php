<?php

declare(strict_types=1);

namespace App\Domain\Nova\Capabilities;

final readonly class CapabilityResolver
{
    public function __construct(
        private CapabilityRegistry $registry,
        private CapabilityGraph $graph,
    ) {}

    /**
     * @return array<int, Capability>
     */
    public function resolve(string $intent, BusinessBlueprint $blueprint): array
    {
        $normalized = mb_strtolower($intent);
        $blueprintCapabilities = array_map(
            fn (string $id): Capability => $this->registry->get($id),
            $blueprint->capabilityIds,
        );

        $matched = array_filter(
            $blueprintCapabilities,
            static fn (Capability $capability): bool => $capability->status === 'active'
                && array_any(
                    $blueprint->businessTypes ?: [$blueprint->name],
                    static fn (string $businessType): bool => $capability->supports($businessType),
                )
                && array_any(
                    $capability->intentTerms,
                    static fn (string $term): bool => str_contains($normalized, $term),
                ),
        );

        if ($matched === []) {
            $matched = array_filter(
                $blueprintCapabilities,
                static fn (Capability $capability): bool => in_array($capability->id, ['knowledge', 'reporting'], true),
            );
        }

        usort(
            $matched,
            static fn (Capability $left, Capability $right): int => $right->priority <=> $left->priority,
        );

        $matchedIds = array_column($matched, 'id');
        $matchedIds[] = 'reporting';

        return $this->graph->resolve(array_values(array_unique($matchedIds)));
    }
}
