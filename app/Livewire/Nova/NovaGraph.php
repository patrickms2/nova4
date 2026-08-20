<?php

declare(strict_types=1);

namespace App\Livewire\Nova;

use App\Domain\Nova\Studio\Workspace\Capabilities\CapabilityCatalog;
use App\Domain\Nova\Studio\Workspace\Graph\GraphBuilder;
use App\Domain\Nova\Studio\Workspace\WorkspaceEvolution;
use App\Domain\Nova\Studio\Workspace\WorkspaceModel;
use App\Domain\Nova\Studio\Workspace\WorkspaceRegistry;
use App\Models\NovaGraphLayout;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Live System Graph — editable slice.
 *
 * Reconstructs nodes/edges from the active Workspace (NOVA Definition)
 * on every render/mutation. Rendering and layout (Dagre auto-layout,
 * pan/zoom) are handled client-side by Alpine Flow; NOVA never stores
 * or dictates node coordinates. See NOVA_GRAPH.md.
 *
 * User-driven graph edits (adding a capability, a custom action, or a
 * manual relation) mutate the real Workspace Definition through the
 * existing WorkspaceEvolution/WorkspaceRegistry pipeline — the graph is
 * never a parallel source of truth.
 */
final class NovaGraph extends Component
{
    public string $workspaceId = '';

    public string $businessName = '';

    /** @var array<int, array<string, mixed>> */
    public array $nodes = [];

    /** @var array<int, array<string, mixed>> */
    public array $edges = [];

    public ?string $selectedAddress = null;

    public function mount(): void
    {
        $workspace = app(WorkspaceRegistry::class)->active();

        if ($workspace === null) {
            return;
        }

        $this->workspaceId = (string) $workspace['id'];
        $this->businessName = (string) ($workspace['business_name'] ?? 'Workspace');

        $this->loadGraph($workspace);
    }

    public function selectNode(string $address): void
    {
        $this->selectedAddress = $address;
    }

    public function closeInspector(): void
    {
        $this->selectedAddress = null;
    }

    /** @return array<int, array<string, mixed>> */
    public function availableCapabilities(): array
    {
        $workspace = $this->activeWorkspace();

        if ($workspace === null) {
            return [];
        }

        return app(WorkspaceEvolution::class)->recommendations($workspace);
    }

    /**
     * Groups each active Capability with its predefined tools (the same
     * `tools` list a Capability already carries from
     * `CapabilityCatalog::businessAreas()`/`improvements()`), so the
     * sidebar can offer real per-capability actions instead of a generic
     * free-text prompt. Only capabilities with at least one tool are
     * returned. Each tool reports whether it has already been added as a
     * custom Action node (and that node's address), so the sidebar can
     * toggle between "add" and "remove" for the same tool.
     *
     * @return array<int, array{id: string, icon: string, name: string, tools: array<int, array{label: string, added: bool, address: ?string}>}>
     */
    public function availableTools(): array
    {
        $workspace = $this->activeWorkspace();

        if ($workspace === null) {
            return [];
        }

        $customActions = collect($workspace['custom_actions'] ?? []);

        return collect($workspace['capabilities'] ?? [])
            ->filter(fn (array $capability): bool => ! empty($capability['tools']))
            ->map(function (array $capability) use ($customActions): array {
                $tools = collect($capability['tools'])->map(function (string $label) use ($customActions, $capability): array {
                    $existing = $customActions->first(
                        fn (array $customAction): bool => ($customAction['label'] ?? null) === $label
                            && ($customAction['capability_id'] ?? null) === $capability['id'],
                    );

                    return [
                        'label' => $label,
                        'added' => $existing !== null,
                        'address' => $existing === null
                            ? null
                            : 'studio.graph.node.action.custom.'.($existing['id'] ?? Str::slug($label)),
                    ];
                })->all();

                return [
                    'id' => $capability['id'],
                    'icon' => $capability['icon'] ?? '◇',
                    'name' => $capability['name'] ?? $capability['id'],
                    'tools' => $tools,
                ];
            })
            ->values()
            ->all();
    }

    public function addCapability(string $improvementId): void
    {
        $workspace = $this->activeWorkspace();

        if ($workspace === null) {
            return;
        }

        $availableIds = array_column($this->availableCapabilities(), 'id');

        if (! in_array($improvementId, $availableIds, true)) {
            return;
        }

        $updated = app(WorkspaceEvolution::class)->improve($workspace, $improvementId);
        $updated = $this->rebuildCapabilityDerivedData($updated);

        $updated = app(WorkspaceRegistry::class)->save($updated);
        $this->loadGraph($updated);
    }

    /**
     * Deletes a node from the Workspace Definition. Only opt-in Capability
     * improvements (`improvement_ids`) can be removed directly — blueprint
     * default areas are re-derived by `WorkspaceEvolution::normalize()` on
     * every save and cannot be turned off this way. A Resource node has no
     * independent existence (it is derived from
     * `WorkspaceModel::entitiesByCapability`), so deleting it cascades to
     * the Capability(ies) that produce it, but only if every producer is
     * itself an opt-in improvement — otherwise the resource would still
     * exist afterwards, so the whole delete is a no-op. Custom Actions are
     * removed directly. The Workspace node and derived (non-custom) Action
     * nodes are never deletable.
     */
    public function deleteNode(string $address): void
    {
        $workspace = $this->activeWorkspace();

        if ($workspace === null) {
            return;
        }

        $capabilityPrefix = 'studio.graph.node.capability.';
        $resourcePrefix = 'studio.graph.node.resource.';
        $customActionPrefix = 'studio.graph.node.action.custom.';

        if (str_starts_with($address, $capabilityPrefix)) {
            $capabilityId = substr($address, strlen($capabilityPrefix));

            if (! in_array($capabilityId, $workspace['improvement_ids'] ?? [], true)) {
                return;
            }

            $workspace = app(WorkspaceEvolution::class)->removeCapability($workspace, $capabilityId);
            $workspace = $this->rebuildCapabilityDerivedData($workspace);
        } elseif (str_starts_with($address, $resourcePrefix)) {
            $producingCapabilityIds = collect($this->edges)
                ->filter(fn (array $edge): bool => $edge['data']['type'] === 'produces' && $edge['target'] === $address)
                ->map(fn (array $edge): string => substr((string) $edge['source'], strlen($capabilityPrefix)))
                ->all();

            $removableIds = array_intersect($producingCapabilityIds, $workspace['improvement_ids'] ?? []);

            if ($producingCapabilityIds === [] || count($removableIds) !== count($producingCapabilityIds)) {
                return;
            }

            foreach ($removableIds as $capabilityId) {
                $workspace = app(WorkspaceEvolution::class)->removeCapability($workspace, $capabilityId);
            }

            $workspace = $this->rebuildCapabilityDerivedData($workspace);
        } elseif (str_starts_with($address, $customActionPrefix)) {
            $workspace['custom_actions'] = array_values(array_filter(
                $workspace['custom_actions'] ?? [],
                static fn (array $customAction): bool => $customActionPrefix.((string) ($customAction['id'] ?? Str::slug((string) ($customAction['label'] ?? '')))) !== $address,
            ));
        } else {
            return;
        }

        $remainingAddresses = array_column(app(GraphBuilder::class)->build($workspace)['nodes'], 'address');
        $workspace['custom_relations'] = array_values(array_filter(
            $workspace['custom_relations'] ?? [],
            static fn (array $relation): bool => in_array($relation['from'] ?? null, $remainingAddresses, true)
                && in_array($relation['to'] ?? null, $remainingAddresses, true),
        ));

        $updated = app(WorkspaceRegistry::class)->save($workspace);
        $this->loadGraph($updated);

        if ($this->selectedAddress !== null && ! in_array($this->selectedAddress, array_column($this->nodes, 'id'), true)) {
            $this->selectedAddress = null;
        }
    }

    public function addCustomAction(string $label, string $capabilityId): void
    {
        $label = trim($label);
        $workspace = $this->activeWorkspace();

        if ($label === '' || $workspace === null || ! in_array($capabilityId, $workspace['capability_ids'] ?? [], true)) {
            return;
        }

        $workspace['custom_actions'] ??= [];
        $workspace['custom_actions'][] = [
            'id' => (string) Str::uuid(),
            'label' => $label,
            'capability_id' => $capabilityId,
        ];

        $updated = app(WorkspaceRegistry::class)->save($workspace);
        $this->loadGraph($updated);
    }

    public function addRelation(string $from, string $to, string $type): void
    {
        $type = trim($type) !== '' ? trim($type) : 'relatesTo';
        $workspace = $this->activeWorkspace();

        if ($from === $to || $workspace === null) {
            return;
        }

        $addresses = array_column($this->nodes, 'id');

        if (! in_array($from, $addresses, true) || ! in_array($to, $addresses, true)) {
            return;
        }

        $workspace['custom_relations'] ??= [];
        $workspace['custom_relations'][] = ['from' => $from, 'to' => $to, 'type' => $type];

        $updated = app(WorkspaceRegistry::class)->save($workspace);
        $this->loadGraph($updated);
    }

    public function saveLayout(string $address, int $x, int $y): void
    {
        if ($this->workspaceId === '') {
            return;
        }

        NovaGraphLayout::query()->updateOrCreate(
            ['workspace_id' => $this->workspaceId, 'node_address' => $address],
            ['x' => $x, 'y' => $y],
        );
    }

    /** @return array<string, mixed>|null */
    public function selectedNode(): ?array
    {
        if ($this->selectedAddress === null) {
            return null;
        }

        return collect($this->nodes)->firstWhere('id', $this->selectedAddress);
    }

    /** @return array<int, array<string, mixed>> */
    public function relationsFor(string $address): array
    {
        return collect($this->edges)
            ->filter(fn (array $edge): bool => $edge['source'] === $address || $edge['target'] === $address)
            ->values()
            ->all();
    }

    public function render(): View
    {
       return view('livewire.nova.nova-graph')->layout('layouts.nova');
       // return view('livewire.react-flow-builder')->layout('layouts.nova');
    }

    /** @return array<string, mixed>|null */
    private function activeWorkspace(): ?array
    {
        return app(WorkspaceRegistry::class)->active();
    }

    /**
     * Recomputes `capabilities`/`operational_model` after `capability_ids`
     * changes (add or remove), reusing the same derivation pipeline
     * `WorkspaceBuilder` uses at creation time — never a parallel source
     * of truth for capability-derived data.
     *
     * @param  array<string, mixed>  $workspace
     * @return array<string, mixed>
     */
    private function rebuildCapabilityDerivedData(array $workspace): array
    {
        $workspace['capabilities'] = app(CapabilityCatalog::class)->forIds($workspace['capability_ids']);
        $workspace['operational_model'] = [
            ...$workspace['operational_model'],
            ...app(WorkspaceModel::class)->build($workspace['capability_ids']),
        ];

        return $workspace;
    }

    /**
     * Maps GraphBuilder's semantic {address, type, label, meta} shape onto
     * the {id, type, data} shape expected by the Alpine Flow node/edge
     * renderer. Node positions are computed client-side by Dagre; NOVA
     * never stores or dictates layout coordinates for this view.
     *
     * @param  array<string, mixed>  $workspace
     */
    private function loadGraph(array $workspace): void
    {
        $graph = app(GraphBuilder::class)->build($workspace);
        $improvementIds = $workspace['improvement_ids'] ?? [];
        $capabilityPrefix = 'studio.graph.node.capability.';

        $producingCapabilitiesByResource = [];

        foreach ($graph['edges'] as $edge) {
            if ($edge['type'] === 'produces') {
                $producingCapabilitiesByResource[$edge['to']][] = $edge['from'];
            }
        }

        $this->nodes = array_map(function (array $node) use ($improvementIds, $capabilityPrefix, $producingCapabilitiesByResource): array {
            $producers = $producingCapabilitiesByResource[$node['address']] ?? [];

            $deletable = match ($node['type']) {
                'capability' => in_array(substr($node['address'], strlen($capabilityPrefix)), $improvementIds, true),
                'action' => (bool) ($node['meta']['custom'] ?? false),
                'resource' => $producers !== [] && collect($producers)->every(
                    fn (string $capabilityAddress): bool => in_array(substr($capabilityAddress, strlen($capabilityPrefix)), $improvementIds, true),
                ),
                default => false,
            };

            return [
                'id' => $node['address'],
                'type' => $node['type'],
                'data' => [
                    'label' => $node['label'],
                    'meta' => [...$node['meta'], 'deletable' => $deletable],
                ],
            ];
        }, $graph['nodes']);

        $this->edges = array_map(static fn (array $edge): array => [
            'source' => $edge['from'],
            'target' => $edge['to'],
            'data' => ['type' => $edge['type']],
        ], $graph['edges']);
    }
}
