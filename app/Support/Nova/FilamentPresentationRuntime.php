<?php

declare(strict_types=1);

namespace App\Support\Nova;

use App\Enums\Nova\NovaPresentationNodeType;
use App\Models\Nova\NovaPresentationNode;
use App\Models\Nova\NovaRepresentation;
use Illuminate\Support\Collection;

final class FilamentPresentationRuntime
{
    /**
     * @return Collection<int,NovaPresentationNode>
     */
    public function subnavigation(NovaRepresentation $representation): Collection
    {
        return NovaPresentationNode::query()
            ->where('representation_id', $representation->id)
            ->where('visible', true)
            ->whereIn('node_type', [
                NovaPresentationNodeType::Relation,
                NovaPresentationNodeType::Table,
                NovaPresentationNodeType::Kanban,
                NovaPresentationNodeType::Tree,
                NovaPresentationNodeType::Infolist,
            ])
            ->orderBy('sort')
            ->get();
    }

    public function settings(
        NovaRepresentation $representation,
        NovaPresentationNodeType $type,
        ?int $parentId = null
    ): array {
        $node = NovaPresentationNode::query()
            ->where('representation_id', $representation->id)
            ->where('node_type', $type)
            ->when($parentId, fn ($query) => $query->where('parent_id', $parentId))
            ->orderBy('sort')
            ->first();

        return $node?->settings ?? [];
    }
}
