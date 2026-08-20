<?php

declare(strict_types=1);

namespace App\Support\Nova;

use App\Models\Nova\NovaBinding;
use App\Models\Nova\NovaCapability;
use App\Models\Nova\NovaConnector;
use App\Models\Nova\NovaGroup;
use App\Models\Nova\NovaPanel;
use App\Models\Nova\NovaRelation;
use App\Models\Nova\NovaResource;
use App\Models\Nova\NovaTool;
use App\Models\Nova\NovaWorkspace;
use Illuminate\Support\Facades\File;

final class NovaDefinitionService
{
    public function exportWorkspace(int $workspaceId): array
    {
        $workspace = NovaWorkspace::query()
            ->with([
                'panels.groups.bindings.capability.tools',
                'panels.groups.bindings.resource',
                'panels.groups.bindings.relation',
                'panels.groups.bindings.connector',
            ])
            ->findOrFail($workspaceId);

        return [
            'version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'workspace' => [
                'key' => $workspace->key,
                'name' => $workspace->name,
                'description' => $workspace->description,
                'settings' => $workspace->settings,
                'panels' => $workspace->panels->map(fn (NovaPanel $panel): array => [
                    'key' => $panel->key,
                    'name' => $panel->name,
                    'description' => $panel->description,
                    'icon' => $panel->icon,
                    'sort' => $panel->sort,
                    'settings' => $panel->settings,
                    'groups' => $panel->groups->map(fn (NovaGroup $group): array => [
                        'key' => $group->key,
                        'name' => $group->name,
                        'icon' => $group->icon,
                        'sort' => $group->sort,
                        'settings' => $group->settings,
                        'bindings' => $group->bindings->map(fn (NovaBinding $binding): array => [
                            'target_type' => $binding->target_type?->value,
                            'capability' => $binding->capability?->key,
                            'tool' => $binding->tool?->key,
                            'resource' => $binding->resource?->key,
                            'relation' => $binding->relation?->key,
                            'connector' => $binding->connector?->key,
                            'role' => $binding->role,
                            'representation' => $binding->representation?->value,
                            'visible' => $binding->visible,
                            'sort' => $binding->sort,
                            'settings' => $binding->settings,
                        ])->values()->all(),
                    ])->values()->all(),
                ])->values()->all(),
            ],
            'catalog' => [
                'capabilities' => NovaCapability::query()
                    ->whereHas('bindings.panel', fn ($query) => $query->where('workspace_id', $workspace->id))
                    ->with('tools')
                    ->get()
                    ->map(fn (NovaCapability $capability): array => [
                        'key' => $capability->key,
                        'name' => $capability->name,
                        'description' => $capability->description,
                        'icon' => $capability->icon,
                        'status' => $capability->status,
                        'settings' => $capability->settings,
                        'tools' => $capability->tools->map(fn (NovaTool $tool): array => [
                            'key' => $tool->key,
                            'name' => $tool->name,
                            'type' => $tool->type?->value,
                            'handler' => $tool->handler,
                            'settings' => $tool->settings,
                        ])->values()->all(),
                    ])->values()->all(),
                'resources' => NovaResource::query()->orderBy('key')->get()->map(fn (NovaResource $resource): array => [
                    'key' => $resource->key,
                    'name' => $resource->name,
                    'type' => $resource->type?->value,
                    'class_name' => $resource->class_name,
                    'source' => $resource->source,
                    'settings' => $resource->settings,
                ])->values()->all(),
                'relations' => NovaRelation::query()->with(['sourceResource', 'targetResource'])->orderBy('key')->get()
                    ->map(fn (NovaRelation $relation): array => [
                        'key' => $relation->key,
                        'name' => $relation->name,
                        'type' => $relation->type?->value,
                        'source' => $relation->sourceResource?->key,
                        'target' => $relation->targetResource?->key,
                        'relation_name' => $relation->relation_name,
                        'settings' => $relation->settings,
                    ])->values()->all(),
                'connectors' => NovaConnector::query()->orderBy('key')->get()->map(fn (NovaConnector $connector): array => [
                    'key' => $connector->key,
                    'name' => $connector->name,
                    'type' => $connector->type?->value,
                    'direction' => $connector->direction?->value,
                    'adapter' => $connector->adapter,
                    'endpoint' => $connector->endpoint,
                    'status' => $connector->status,
                    'settings' => $connector->settings,
                ])->values()->all(),
            ],
        ];
    }

    public function exportAll(): array
    {
        return [
            'nova' => 'NOVA4',
            'definition_version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'workspaces' => NovaWorkspace::query()
                ->orderBy('id')
                ->pluck('id')
                ->map(fn (int $workspaceId): array => $this->exportWorkspace($workspaceId))
                ->all(),
        ];
    }

    public function exportWorkspaceToStorage(int $workspaceId): string
    {
        $workspace = NovaWorkspace::query()->findOrFail($workspaceId);
        $path = storage_path('app/nova/definitions/'.$workspace->key.'.json');

        $this->write($path, $this->exportWorkspace($workspaceId));

        return $path;
    }

    public function exportAllToStorage(): string
    {
        $path = storage_path('app/nova/definition.json');
        $this->write($path, $this->exportAll());

        return $path;
    }

    private function write(string $path, array $definition): void
    {
        File::ensureDirectoryExists(dirname($path));
        File::put(
            $path,
            json_encode($definition, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}
