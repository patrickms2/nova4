<?php

declare(strict_types=1);

namespace App\Support\Nova;

use App\Enums\Nova\NovaBindingTarget;
use App\Enums\Nova\NovaRepresentationType;
use App\Enums\Nova\NovaResourceType;
use App\Enums\Nova\NovaToolType;
use App\Models\Nova\NovaBinding;
use App\Models\Nova\NovaCapability;
use App\Models\Nova\NovaGroup;
use App\Models\Nova\NovaPanel;
use App\Models\Nova\NovaResource;
use App\Models\Nova\NovaTool;
use App\Models\Nova\NovaRelation;
use App\Models\Nova\NovaConnector;


use App\Models\Nova\NovaWorkspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

final class NovaDefinitionService
{
    public function ensureCommunityDefinition(): NovaWorkspace
    {
        $existing = NovaWorkspace::query()->where('key', 'community')->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function (): NovaWorkspace {
            $workspace = NovaWorkspace::query()->create([
                'key' => 'community',
                'name' => 'NOVA Community',
                'description' => 'Community operations workspace.',
                'status' => 'active',
                'settings' => ['source' => 'nova-studio'],
            ]);

            $panel = NovaPanel::query()->create([
                'workspace_id' => $workspace->id,
                'key' => 'community',
                'name' => 'NOVA Community',
                'description' => 'Panel principal de Community.',
                'icon' => 'heroicon-o-building-office-2',
                'sort' => 10,
                'settings' => [],
            ]);

            $groups = [
                'property' => ['Propiedad', 10],
                'community' => ['Comunidad', 20],
                'maintenance' => ['Mantenimiento', 30],
                'access' => ['NOVA Access', 40],
            ];

            foreach ($groups as $key => [$name, $sort]) {
                NovaGroup::query()->create([
                    'panel_id' => $panel->id,
                    'key' => $key,
                    'name' => $name,
                    'sort' => $sort,
                    'settings' => [],
                ]);
            }

            $definition = [
                'property' => [
                    ['properties', 'Propiedades', ['view', 'edit', 'documents'], ['owner', 'manager']],
                    ['documents', 'Documentos', ['view', 'upload', 'download'], ['owner', 'employee', 'manager']],
                    ['fees', 'Cuotas', ['view', 'download'], ['owner', 'manager']],
                ],
                'community' => [
                    ['communities', 'Comunidades', ['view'], ['employee', 'manager']],
                    ['notices', 'Avisos', ['view'], ['owner', 'employee', 'manager']],
                    ['incidents', 'Incidencias', ['view', 'create', 'photo', 'priority', 'resolve'], ['owner', 'employee', 'manager']],
                    ['tickets', 'Tickets', ['view', 'create', 'comment'], ['owner', 'employee', 'manager']],
                    ['appointments', 'Citas', ['view', 'request', 'confirm'], ['owner', 'employee', 'manager']],
                ],
                'maintenance' => [
                    ['plans', 'Planes', ['view', 'generate-orders'], ['employee', 'manager']],
                    ['work-orders', 'Órdenes', ['view', 'start', 'complete', 'assign'], ['employee', 'manager']],
                    ['tasks', 'Tareas', ['view', 'check'], ['employee', 'manager']],
                    ['shifts', 'Turnos', ['view', 'start', 'finish'], ['employee', 'manager']],
                    ['attendance', 'Asistencia', ['view', 'register', 'voice-summary'], ['employee', 'manager']],
                    ['expenses', 'Gastos', ['view', 'create', 'ocr'], ['employee', 'manager']],
                ],
                'access' => [
                    ['credentials', 'Credenciales', ['view', 'create', 'revoke'], ['owner', 'employee', 'manager']],
                    ['access-grants', 'Access Grants', ['view', 'create', 'revoke'], ['manager']],
                    ['access-points', 'Access Points', ['view', 'manage'], ['manager']],
                    ['devices', 'Devices', ['view', 'manage'], ['manager']],
                ],
            ];

            $sort = 10;

            foreach ($definition as $groupKey => $capabilities) {
                $group = NovaGroup::query()
                    ->where('panel_id', $panel->id)
                    ->where('key', $groupKey)
                    ->firstOrFail();

                foreach ($capabilities as [$key, $name, $tools, $roles]) {
                    $capability = NovaCapability::query()->firstOrCreate(
                        ['key' => 'community.'.$key],
                        [
                            'name' => $name,
                            'description' => null,
                            'status' => 'active',
                            'settings' => ['section' => $key],
                        ]
                    );

                    foreach ($tools as $toolSort => $toolKey) {
                        NovaTool::query()->firstOrCreate(
                            ['capability_id' => $capability->id, 'key' => $toolKey],
                            [
                                'name' => str($toolKey)->replace('-', ' ')->headline()->toString(),
                                'type' => $toolKey === 'view' ? NovaToolType::View : NovaToolType::Action,
                                'sort' => ($toolSort + 1) * 10,
                                'settings' => [],
                            ]
                        );
                    }

                    foreach ($roles as $role) {
                        $representation = $role === 'manager'
                            ? NovaRepresentationType::Filament
                            : NovaRepresentationType::Livewire;

                        NovaBinding::query()->create([
                            'panel_id' => $panel->id,
                            'group_id' => $group->id,
                            'capability_id' => $capability->id,
                            'target_type' => NovaBindingTarget::Capability,
                            'role' => $role,
                            'representation' => $representation,
                            'visible' => true,
                            'sort' => $sort,
                            'settings' => ['label' => $name],
                        ]);
                    }

                    $sort += 10;
                }
            }

            $this->seedKnownResources();

            return $workspace->fresh();
        });
    }
   public function exportCommunityDefinition(): array
    {
        $workspace = $this->ensureCommunityDefinition();
        $workspace->load([
            'panels.groups.bindings.capability.tools',
            'panels.groups.bindings.resource',
            'panels.groups.bindings.connector',
        ]);

        return [
            'workspace' => [
                'key' => $workspace->key,
                'name' => $workspace->name,
            ],
            'panels' => $workspace->panels->map(fn (NovaPanel $panel): array => [
                'key' => $panel->key,
                'name' => $panel->name,
                'groups' => $panel->groups->map(fn (NovaGroup $group): array => [
                    'key' => $group->key,
                    'name' => $group->name,
                    'sort' => $group->sort,
                    'bindings' => $group->bindings->map(fn (NovaBinding $binding): array => [
                        'capability' => $binding->capability?->key,
                        'role' => $binding->role,
                        'representation' => $binding->representation?->value,
                        'visible' => $binding->visible,
                        'sort' => $binding->sort,
                        'settings' => $binding->settings,
                    ])->values()->all(),
                ])->values()->all(),
            ])->values()->all(),
        ];
    }
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
    public function exportToStorage(): string
    {
        $path = storage_path('app/nova/definition.json');
        File::ensureDirectoryExists(dirname($path));
        File::put(
            $path,
            json_encode($this->exportCommunityDefinition(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return $path;
    }
    private function seedKnownResources(): void
    {
        $resources = [
            ['incident', 'Incident', 'App\\Models\\Incident'],
            ['property', 'Property', 'App\\Models\\Property'],
            ['community', 'Community', 'App\\Models\\Community'],
            ['work-order', 'WorkOrder', 'App\\Models\\WorkOrder'],
            ['employee', 'Employee', 'App\\Models\\Employee'],
            ['person', 'Person', 'App\\Models\\Person'],
        ];

        foreach ($resources as [$key, $name, $class]) {
            NovaResource::query()->firstOrCreate(
                ['key' => $key],
                [
                    'name' => $name,
                    'type' => NovaResourceType::EloquentModel,
                    'class_name' => $class,
                    'settings' => [],
                ]
            );
        }
    }
}
