<?php

declare(strict_types=1);

namespace App\Domain\Nova\Studio\Workspace\Graph;

use App\Domain\Nova\Studio\Workspace\WorkspaceModel;
use Illuminate\Support\Str;

/**
 * Reconstructs the Live System Graph (nodes + relations) from canonical
 * NOVA Definition data (Workspace/Capability), never from a separate
 * stored diagram. See NOVA_GRAPH.md.
 */
final readonly class GraphBuilder
{
    public function __construct(private WorkspaceModel $model) {}

    /**
     * @param  array<string, mixed>  $workspace
     * @return array{nodes: array<int, array<string, mixed>>, edges: array<int, array<string, mixed>>}
     */
    public function build(array $workspace): array
    {
        $capabilityIds = array_values(array_unique($workspace['capability_ids'] ?? []));
        $capabilitiesById = collect($workspace['capabilities'] ?? [])->keyBy('id');
        $entitiesByCapability = $this->model->entitiesByCapability($capabilityIds);
        $processesByCapability = $this->model->processesByCapability($capabilityIds);
        $relations = $workspace['operational_model']['relations'] ?? [];

        $nodes = [];
        $edges = [];

        $workspaceAddress = 'studio.graph.node.workspace';
        $nodes[$workspaceAddress] = [
            'address' => $workspaceAddress,
            'type' => 'workspace',
            'label' => $workspace['business_name'] ?? 'Workspace',
            'meta' => [
                'icon' => $workspace['business_icon'] ?? '✦',
                'blueprint_id' => $workspace['blueprint_id'] ?? null,
            ],
        ];

        foreach ($capabilityIds as $capabilityId) {
            $capability = $capabilitiesById->get($capabilityId, ['name' => $capabilityId, 'icon' => '◇']);
            $capabilityAddress = "studio.graph.node.capability.{$capabilityId}";

            $nodes[$capabilityAddress] = [
                'address' => $capabilityAddress,
                'type' => 'capability',
                'label' => $capability['name'] ?? $capabilityId,
                'meta' => $capability,
            ];
            $edges[] = ['from' => $workspaceAddress, 'to' => $capabilityAddress, 'type' => 'owns'];

            if (isset($entitiesByCapability[$capabilityId])) {
                $entity = $entitiesByCapability[$capabilityId];
                $resourceAddress = 'studio.graph.node.resource.'.Str::slug($entity);

                $nodes[$resourceAddress] ??= [
                    'address' => $resourceAddress,
                    'type' => 'resource',
                    'label' => $entity,
                    'meta' => [],
                ];
                $edges[] = ['from' => $capabilityAddress, 'to' => $resourceAddress, 'type' => 'produces'];
            }

            if (isset($processesByCapability[$capabilityId])) {
                $process = $processesByCapability[$capabilityId];
                $actionAddress = 'studio.graph.node.action.'.Str::slug($process);

                $nodes[$actionAddress] ??= [
                    'address' => $actionAddress,
                    'type' => 'action',
                    'label' => $process,
                    'meta' => [],
                ];
                $edges[] = ['from' => $capabilityAddress, 'to' => $actionAddress, 'type' => 'supports'];
            }
        }

        foreach ($relations as $relation) {
            $fromAddress = 'studio.graph.node.resource.'.Str::slug((string) ($relation['from'] ?? ''));
            $toAddress = 'studio.graph.node.resource.'.Str::slug((string) ($relation['to'] ?? ''));

            if (! isset($nodes[$fromAddress]) || ! isset($nodes[$toAddress])) {
                continue;
            }

            $edges[] = [
                'from' => $fromAddress,
                'to' => $toAddress,
                'type' => (string) ($relation['type'] ?? 'relatesTo'),
            ];
        }

        foreach ($workspace['custom_actions'] ?? [] as $customAction) {
            $capabilityAddress = 'studio.graph.node.capability.'.($customAction['capability_id'] ?? '');
            $actionAddress = 'studio.graph.node.action.custom.'.($customAction['id'] ?? Str::slug((string) ($customAction['label'] ?? '')));

            if (! isset($nodes[$capabilityAddress])) {
                continue;
            }

            $nodes[$actionAddress] = [
                'address' => $actionAddress,
                'type' => 'action',
                'label' => (string) ($customAction['label'] ?? ''),
                'meta' => ['custom' => true],
            ];
            $edges[] = ['from' => $capabilityAddress, 'to' => $actionAddress, 'type' => 'supports'];
        }

        foreach ($workspace['custom_relations'] ?? [] as $customRelation) {
            $fromAddress = (string) ($customRelation['from'] ?? '');
            $toAddress = (string) ($customRelation['to'] ?? '');

            if (! isset($nodes[$fromAddress]) || ! isset($nodes[$toAddress])) {
                continue;
            }

            $edges[] = [
                'from' => $fromAddress,
                'to' => $toAddress,
                'type' => (string) ($customRelation['type'] ?? 'relatesTo'),
            ];
        }

        return [
            'nodes' => array_values($nodes),
            'edges' => $edges,
        ];
    }
}
