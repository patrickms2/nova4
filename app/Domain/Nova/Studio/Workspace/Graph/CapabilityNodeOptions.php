<?php

declare(strict_types=1);

namespace App\Domain\Nova\Studio\Workspace\Graph;

use App\Domain\Nova\Studio\Workspace\WorkspaceModel;
use Illuminate\Support\Str;

/**
 * Resolves which Actions and Resources are compatible with a given
 * Capability, from the same canonical NOVA Definition source GraphBuilder
 * uses (WorkspaceModel::entityFor()/processFor()). This is read by
 * VoodFlow to power the Capability "+" contextual menu, but VoodFlow
 * never owns or duplicates this knowledge — see NOVA_GRAPH.md → "VOODFLOW".
 */
final readonly class CapabilityNodeOptions
{
    private const CAPABILITY_PREFIX = 'studio.graph.node.capability.';

    private const ACTION_PREFIX = 'studio.graph.node.action.';

    private const RESOURCE_PREFIX = 'studio.graph.node.resource.';

    /**
     * @return array{
     *     capability: string,
     *     actions: array<int, array{address: string, label: string}>,
     *     resources: array<int, array{address: string, label: string}>
     * }
     */
    public function forCapability(string $capabilityId): array
    {
        $actions = [];
        $resources = [];

        $process = WorkspaceModel::processFor($capabilityId);
        if ($process !== null) {
            $actions[] = [
                'address' => self::ACTION_PREFIX.Str::slug($process),
                'label' => $process,
            ];
        }

        $entity = WorkspaceModel::entityFor($capabilityId);
        if ($entity !== null) {
            $resources[] = [
                'address' => self::RESOURCE_PREFIX.Str::slug($entity),
                'label' => $entity,
            ];
        }

        return [
            'capability' => $capabilityId,
            'actions' => $actions,
            'resources' => $resources,
        ];
    }

    /**
     * Extracts the Capability id from a full NOVA Address such as
     * "studio.graph.node.capability.reservations", or from the
     * "NOVA Address: <address>" description string VoodFlow stores on
     * imported capability nodes.
     */
    public static function capabilityIdFromDescription(?string $description): ?string
    {
        if ($description === null) {
            return null;
        }

        $address = Str::after($description, 'NOVA Address:');
        $address = trim($address);

        if (! Str::startsWith($address, self::CAPABILITY_PREFIX)) {
            return null;
        }

        $capabilityId = Str::after($address, self::CAPABILITY_PREFIX);

        return $capabilityId === '' ? null : $capabilityId;
    }
}
