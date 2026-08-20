<?php

declare(strict_types=1);

namespace App\Support\Nova;

use App\Enums\Nova\NovaFormMode;
use App\Enums\Nova\NovaInfolistLayout;
use App\Enums\Nova\NovaPresentationNodeType;
use App\Enums\Nova\NovaTableViewMode;
use App\Models\Nova\NovaPresentationNode;
use App\Models\Nova\NovaRelation;
use App\Models\Nova\NovaRepresentation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class FilamentPresentationBuilder
{
    public function build(NovaRepresentation $representation): NovaPresentationNode
    {
        $representation->loadMissing(['resource', 'capability']);

        $root = NovaPresentationNode::query()->updateOrCreate(
            [
                'representation_id' => $representation->id,
                'key' => 'root',
            ],
            [
                'parent_id' => null,
                'node_type' => NovaPresentationNodeType::Capability,
                'capability_id' => $representation->capability_id,
                'resource_id' => $representation->resource_id,
                'label' => $representation->navigation_label ?: $representation->name,
                'icon' => $representation->navigation_icon,
                'sort' => 10,
                'visible' => true,
                'settings' => [
                    'subnavigation' => true,
                    'navigation_group' => $representation->navigation_group,
                ],
            ]
        );

        $this->buildPrimaryViews($representation, $root);
        $this->buildRelations($representation, $root);

        return $root->fresh(['children.children']);
    }

    private function buildPrimaryViews(NovaRepresentation $representation, NovaPresentationNode $root): void
    {
        $columns = $this->columnsFor($representation);

        $hasStatus = collect($columns)->contains(fn (string $column): bool => in_array($column, [
            'status', 'state', 'stage',
        ], true));

        $hasParent = collect($columns)->contains(fn (string $column): bool => in_array($column, [
            'parent_id', 'category_id',
        ], true));

        $table = $this->node(
            $representation,
            $root,
            NovaPresentationNodeType::Table,
            'table',
            'Tabla',
            10,
            [
                'enabled' => true,
                'default_view' => NovaTableViewMode::Table->value,
                'filters_enabled' => true,
            ]
        );

        if ($hasStatus) {
            $this->node(
                $representation,
                $table,
                NovaPresentationNodeType::Tabs,
                'tabs',
                'Tabs por estado',
                10,
                [
                    'enabled' => true,
                    'field' => 'status',
                ]
            );

            $this->node(
                $representation,
                $table,
                NovaPresentationNodeType::Kanban,
                'kanban',
                'Kanban',
                20,
                [
                    'enabled' => true,
                    'status_field' => 'status',
                ]
            );
        }

        if ($hasParent) {
            $this->node(
                $representation,
                $table,
                NovaPresentationNodeType::Tree,
                'tree',
                'Árbol',
                30,
                [
                    'enabled' => true,
                    'parent_field' => collect($columns)->contains('parent_id') ? 'parent_id' : 'category_id',
                ]
            );
        }

        $this->node(
            $representation,
            $table,
            NovaPresentationNodeType::Filters,
            'filters',
            'Filtros',
            40,
            [
                'enabled' => true,
                'auto_detect' => true,
            ]
        );

        $this->node(
            $representation,
            $root,
            NovaPresentationNodeType::Form,
            'form',
            'Formulario',
            20,
            [
                'create_mode' => NovaFormMode::Modal->value,
                'edit_mode' => NovaFormMode::Modal->value,
            ]
        );

        $this->node(
            $representation,
            $root,
            NovaPresentationNodeType::Infolist,
            'infolist',
            'Vista',
            30,
            [
                'layout' => NovaInfolistLayout::Cards->value,
                'cards' => true,
            ]
        );
    }

    private function buildRelations(NovaRepresentation $representation, NovaPresentationNode $root): void
    {
        $resource = $representation->resource;

        if (! $resource) {
            return;
        }

        $relations = NovaRelation::query()
            ->with(['sourceResource', 'targetResource'])
            ->where(function ($query) use ($resource): void {
                $query->where('source_resource_id', $resource->id)
                    ->orWhere('target_resource_id', $resource->id);
            })
            ->orderBy('name')
            ->get();

        $sort = 100;

        foreach ($relations as $relation) {
            $related = $relation->source_resource_id === $resource->id
                ? $relation->targetResource
                : $relation->sourceResource;

            $relationNode = NovaPresentationNode::query()->updateOrCreate(
                [
                    'representation_id' => $representation->id,
                    'key' => 'relation.'.$relation->key,
                ],
                [
                    'parent_id' => $root->id,
                    'node_type' => NovaPresentationNodeType::Relation,
                    'relation_id' => $relation->id,
                    'resource_id' => $related?->id,
                    'label' => $related?->name ?? $relation->name,
                    'icon' => null,
                    'sort' => $sort,
                    'visible' => true,
                    'settings' => [
                        'subnavigation' => true,
                        'relation_name' => $relation->relation_name,
                        'relation_type' => $relation->type?->value,
                    ],
                ]
            );

            $this->buildRelationViews($representation, $relationNode);

            $sort += 10;
        }
    }

    private function buildRelationViews(
        NovaRepresentation $representation,
        NovaPresentationNode $relationNode
    ): void {
        $table = $this->node(
            $representation,
            $relationNode,
            NovaPresentationNodeType::Table,
            $relationNode->key.'.table',
            'Tabla',
            10,
            [
                'enabled' => true,
                'filters_enabled' => true,
            ]
        );

        $this->node(
            $representation,
            $table,
            NovaPresentationNodeType::Filters,
            $relationNode->key.'.filters',
            'Filtros',
            10,
            [
                'enabled' => true,
                'auto_detect' => true,
            ]
        );

        $this->node(
            $representation,
            $relationNode,
            NovaPresentationNodeType::Form,
            $relationNode->key.'.form',
            'Editar',
            20,
            [
                'create_mode' => NovaFormMode::Modal->value,
                'edit_mode' => NovaFormMode::Modal->value,
            ]
        );

        $this->node(
            $representation,
            $relationNode,
            NovaPresentationNodeType::Infolist,
            $relationNode->key.'.infolist',
            'Ver',
            30,
            [
                'layout' => NovaInfolistLayout::Cards->value,
                'cards' => true,
            ]
        );
    }

    private function node(
        NovaRepresentation $representation,
        NovaPresentationNode $parent,
        NovaPresentationNodeType $type,
        string $key,
        string $label,
        int $sort,
        array $settings
    ): NovaPresentationNode {
        return NovaPresentationNode::query()->updateOrCreate(
            [
                'representation_id' => $representation->id,
                'key' => $key,
            ],
            [
                'parent_id' => $parent->id,
                'node_type' => $type,
                'capability_id' => $parent->capability_id,
                'relation_id' => $parent->relation_id,
                'resource_id' => $parent->resource_id,
                'label' => $label,
                'sort' => $sort,
                'visible' => true,
                'settings' => $settings,
            ]
        );
    }

    /**
     * @return array<int,string>
     */
    private function columnsFor(NovaRepresentation $representation): array
    {
        $class = $representation->model_class;

        if (! $class || ! class_exists($class)) {
            return [];
        }

        try {
            $model = app($class);
            $table = $model->getTable();

            return Schema::hasTable($table) ? Schema::getColumnListing($table) : [];
        } catch (\Throwable) {
            return [];
        }
    }
}
