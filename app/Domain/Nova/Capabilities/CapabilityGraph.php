<?php

declare(strict_types=1);

namespace App\Domain\Nova\Capabilities;

use LogicException;

final readonly class CapabilityGraph
{
    public function __construct(private CapabilityRegistry $registry) {}

    /**
     * @param  array<int, string>  $capabilityIds
     * @return array<int, Capability>
     */
    public function resolve(array $capabilityIds): array
    {
        $resolved = [];
        $visiting = [];

        foreach ($capabilityIds as $id) {
            $this->visit($id, $resolved, $visiting);
        }

        return array_values($resolved);
    }

    /**
     * @param  array<string, Capability>  $resolved
     * @param  array<string, true>  $visiting
     */
    private function visit(string $id, array &$resolved, array &$visiting): void
    {
        if (isset($resolved[$id])) {
            return;
        }

        if (isset($visiting[$id])) {
            throw new LogicException("Se ha detectado una dependencia circular en la capacidad [{$id}].");
        }

        $visiting[$id] = true;
        $capability = $this->registry->get($id);

        foreach ($capability->dependencies as $dependency) {
            $this->visit($dependency, $resolved, $visiting);
        }

        unset($visiting[$id]);
        $resolved[$id] = $capability;
    }
}
