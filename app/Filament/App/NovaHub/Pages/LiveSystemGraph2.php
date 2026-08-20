<?php

namespace App\Filament\App\NovaHub\Pages;

use App\Domain\Nova\Studio\Workspace\Graph\GraphBuilder;
use App\Domain\Nova\Studio\Workspace\WorkspaceRegistry;
use App\Models\NovaGraphLayout;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Live System Graph, rendered through Voodflow's canvas
 * (VoodflowAdapter) as a replaceable FLOW infrastructure choice.
 *
 * Nodes/edges are reconstructed from canonical NOVA Definition data via
 * the existing GraphBuilder — never a parallel source of truth. Canvas
 * layout (x/y) is presentation-only, read from NovaGraphLayout when
 * present, or a deterministic default grid otherwise (never stored back
 * from this read-only surface). See NOVA_GRAPH.md → "VOODFLOW" and
 * .codex/tasks/029-NOVA Voodflow Action Nodes.md.
 *
 * This page is read-only. The editable canonical Graph editor remains
 * App\Livewire\Nova\NovaGraph (alpine-flow adapter). This page proves
 * Voodflow's canvas can render the same semantic Node/Relation Contract
 * without becoming canonical storage.
 */
class LiveSystemGraph2 extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShare;

    protected static string|\UnitEnum|null $navigationGroup = 'Ajustes';

    protected static ?string $navigationLabel = 'Live System Graph';

    protected static ?string $title = 'NOVA — Live System Graph';

    protected static ?string $slug = 'live-system-graph';

    protected string $view = 'filament.pages.live-system-graph';

    public function getViewData(): array
    {
        $workspace = app(WorkspaceRegistry::class)->active();

        if ($workspace === null) {
            return ['nodes' => [], 'edges' => [], 'viewport' => ['x' => 0, 'y' => 0, 'zoom' => 0.8]];
        }

        $graph = app(GraphBuilder::class)->build($workspace);

        $layoutByAddress = NovaGraphLayout::query()
            ->where('workspace_id', $workspace['id'])
            ->get()
            ->keyBy('node_address');

        $nodes = [];

        foreach (array_values($graph['nodes']) as $index => $node) {
            $layout = $layoutByAddress->get($node['address']);

            $nodes[] = [
                'id' => $node['address'],
                'type' => $node['type'],
                'position' => $layout !== null
                    ? ['x' => $layout->x, 'y' => $layout->y]
                    : $this->defaultPosition($index),
                'data' => [
                    'label' => $node['label'],
                    'meta' => $node['meta'],
                ],
            ];
        }

        $edges = array_map(static fn (array $edge): array => [
            'id' => "{$edge['from']}--{$edge['to']}--{$edge['type']}",
            'source' => $edge['from'],
            'target' => $edge['to'],
            'sourceHandle' => null,
            'targetHandle' => null,
            'label' => $edge['type'],
        ], $graph['edges']);

        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'viewport' => ['x' => 0, 'y' => 0, 'zoom' => 0.8],
        ];
    }

    /**
     * Deterministic default grid layout used only when no
     * NovaGraphLayout row exists yet for a node. Never persisted from
     * this read-only page — see NOVA_GRAPH.md → "Canvas Layout".
     *
     * @return array{x: int, y: int}
     */
    private function defaultPosition(int $index): array
    {
        $columns = 4;

        return [
            'x' => ($index % $columns) * 260,
            'y' => intdiv($index, $columns) * 160,
        ];
    }
}
