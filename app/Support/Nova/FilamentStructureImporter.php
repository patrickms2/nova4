<?php

declare(strict_types=1);

namespace App\Support\Nova;

use App\Enums\Nova\NovaPresentationNodeType;
use App\Models\Nova\NovaPresentationNode;
use App\Models\Nova\NovaRepresentation;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class FilamentStructureImporter
{
    public function import(NovaRepresentation $representation, array $structure): NovaPresentationNode
    {
        $root = $this->node(
            $representation,
            null,
            NovaPresentationNodeType::Capability,
            'root',
            $structure['navigation']['label'] ?: $representation->name,
            10,
            [
                'source' => 'filament-discovery-2',
                'navigation' => $structure['navigation'],
            ]
        );

        $list = $this->node(
            $representation,
            $root,
            NovaPresentationNodeType::ListView,
            'list',
            'Listado',
            20,
            ['view_type' => 'table']
        );

        $this->importTable($representation, $list, $structure);
        $this->importListPage($representation, $list, $structure);

        $record = $this->node(
            $representation,
            $root,
            NovaPresentationNodeType::RecordView,
            'record',
            'Registro',
            30,
            []
        );

        if ($structure['infolist']) {
            $this->node(
                $representation,
                $record,
                NovaPresentationNodeType::Infolist,
                'record.infolist',
                'Infolist',
                10,
                [
                    'layout' => 'cards',
                    'source_class' => $structure['infolist'],
                    'sections' => Arr::get($structure, 'infolist_details.sections', []),
                ]
            );
        }

        if ($structure['form']) {
            $this->node(
                $representation,
                $record,
                NovaPresentationNodeType::Form,
                'record.form',
                'Formulario',
                20,
                [
                    'create_mode' => 'modal',
                    'edit_mode' => 'modal',
                    'source_class' => $structure['form'],
                    'fields' => Arr::get($structure, 'form_details.fields', []),
                ]
            );
        }

        $this->importQuickNavigation($representation, $record, $structure);
        $this->importRelations($representation, $record, $structure);
        $this->importPages($representation, $root, $structure);
        $this->importWidgets($representation, $list, $structure);

        return $root->fresh(['children.children.children']);
    }

    private function importTable(NovaRepresentation $representation, NovaPresentationNode $list, array $structure): void
    {
        $details = $structure['table_details'] ?? [];

        $table = $this->node(
            $representation,
            $list,
            NovaPresentationNodeType::Table,
            'list.table',
            'Tabla',
            10,
            [
                'source_class' => $structure['table'],
                'columns' => $details['columns'] ?? [],
                'search' => true,
            ]
        );

        $filters = $details['filters'] ?? [];
        if ($filters !== []) {
            $this->node(
                $representation,
                $table,
                NovaPresentationNodeType::Filters,
                'list.table.filters',
                'Filtros',
                10,
                ['items' => $filters]
            );
        }

        $actions = $details['actions'] ?? [];
        if ($actions !== []) {
            $this->node(
                $representation,
                $table,
                NovaPresentationNodeType::Action,
                'list.table.actions',
                'Acciones',
                20,
                ['items' => $actions]
            );
        }
    }

    private function importListPage(NovaRepresentation $representation, NovaPresentationNode $list, array $structure): void
    {
        $page = $structure['page_details']['index'] ?? null;

        if (! $page) {
            return;
        }

        if (($page['tabs'] ?? []) !== []) {
            $this->node(
                $representation,
                $list,
                NovaPresentationNodeType::Tabs,
                'list.tabs',
                'Tabs',
                20,
                ['items' => $page['tabs']]
            );
        }

        if (($page['header_actions'] ?? []) !== []) {
            $this->node(
                $representation,
                $list,
                NovaPresentationNodeType::HeaderActions,
                'list.header-actions',
                'Acciones superiores',
                30,
                ['items' => $page['header_actions']]
            );
        }

        foreach ($page['header_widgets'] ?? [] as $index => $widget) {
            $this->node(
                $representation,
                $list,
                NovaPresentationNodeType::Widget,
                'list.header-widget.'.Str::kebab(class_basename($widget)),
                class_basename($widget),
                40 + ($index * 10),
                ['class' => $widget]
            );
        }
    }

    private function importQuickNavigation(NovaRepresentation $representation, NovaPresentationNode $record, array $structure): void
    {
        $items = $structure['record_subnavigation'] ?? [];

        if ($items === []) {
            return;
        }

        $this->node(
            $representation,
            $record,
            NovaPresentationNodeType::QuickNavigation,
            'record.quick-navigation',
            'Navegación rápida',
            5,
            [
                'items' => array_map(fn (string $class): array => [
                    'class' => $class,
                    'label' => Str::headline(Str::beforeLast(class_basename($class), 'Taxista')),
                ], $items),
            ]
        );
    }

    private function importRelations(NovaRepresentation $representation, NovaPresentationNode $record, array $structure): void
    {
        $sort = 100;

        foreach ($structure['relation_details'] ?? [] as $class => $details) {
            $key = 'relation.'.Str::kebab(class_basename($class));

            $relation = $this->node(
                $representation,
                $record,
                NovaPresentationNodeType::Relation,
                $key,
                $details['title'] ?: Str::headline(Str::beforeLast(class_basename($class), 'RelationManager')),
                $sort,
                [
                    'class' => $class,
                    'relationship' => $details['relationship'],
                    'subnavigation' => true,
                ]
            );

            $this->node(
                $representation,
                $relation,
                NovaPresentationNodeType::Table,
                $key.'.table',
                'Tabla',
                10,
                [
                    'columns' => Arr::get($details, 'table.columns', []),
                ]
            );

            if (Arr::get($details, 'table.filters', []) !== []) {
                $this->node(
                    $representation,
                    $relation,
                    NovaPresentationNodeType::Filters,
                    $key.'.filters',
                    'Filtros',
                    20,
                    ['items' => Arr::get($details, 'table.filters', [])]
                );
            }

            $this->node(
                $representation,
                $relation,
                NovaPresentationNodeType::Form,
                $key.'.form',
                'Formulario',
                30,
                [
                    'edit_mode' => 'modal',
                    'fields' => Arr::get($details, 'form.fields', []),
                ]
            );

            $this->node(
                $representation,
                $relation,
                NovaPresentationNodeType::Infolist,
                $key.'.infolist',
                'Vista',
                40,
                ['layout' => 'cards']
            );

            $sort += 10;
        }
    }

    private function importPages(NovaRepresentation $representation, NovaPresentationNode $root, array $structure): void
    {
        $sort = 200;

        foreach ($structure['page_details'] ?? [] as $key => $page) {
            if ($key === 'index' || $key === 'view' || $key === 'create' || $key === 'edit') {
                continue;
            }

            $type = match ($page['view_type'] ?? 'page') {
                'kanban' => NovaPresentationNodeType::Kanban,
                'calendar' => NovaPresentationNodeType::Calendar,
                'roster' => NovaPresentationNodeType::Roster,
                'map' => NovaPresentationNodeType::Map,
                'timeline' => NovaPresentationNodeType::Timeline,
                default => NovaPresentationNodeType::Page,
            };

            $this->node(
                $representation,
                $root,
                $type,
                'page.'.Str::kebab((string) $key),
                Str::headline((string) $key),
                $sort,
                [
                    'route' => $structure['pages'][$key]['route'] ?? null,
                    'class' => $structure['pages'][$key]['class'] ?? null,
                    'tabs' => $page['tabs'] ?? [],
                    'header_actions' => $page['header_actions'] ?? [],
                    'view' => $page['view'] ?? null,
                ]
            );

            $sort += 10;
        }
    }

    private function importWidgets(NovaRepresentation $representation, NovaPresentationNode $list, array $structure): void
    {
        $sort = 80;

        foreach ($structure['widget_details'] ?? [] as $class => $widget) {
            $type = ($widget['stats'] ?? []) !== []
                ? NovaPresentationNodeType::Stats
                : NovaPresentationNodeType::Widget;

            $this->node(
                $representation,
                $list,
                $type,
                'widget.'.Str::kebab(class_basename($class)),
                Str::headline(Str::beforeLast(class_basename($class), 'Widget')),
                $sort,
                [
                    'class' => $class,
                    'stats' => $widget['stats'] ?? [],
                    'view_type' => $widget['view_type'] ?? 'page',
                ]
            );

            $sort += 10;
        }
    }

    private function node(
        NovaRepresentation $representation,
        ?NovaPresentationNode $parent,
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
                'parent_id' => $parent?->id,
                'node_type' => $type,
                'capability_id' => $representation->capability_id,
                'resource_id' => $representation->resource_id,
                'label' => $label,
                'sort' => $sort,
                'visible' => true,
                'settings' => $settings,
            ]
        );
    }
}
