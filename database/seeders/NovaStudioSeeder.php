<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Nova\NovaBindingTarget;
use App\Enums\Nova\NovaConnectorDirection;
use App\Enums\Nova\NovaConnectorType;
use App\Enums\Nova\NovaRelationType;
use App\Enums\Nova\NovaRepresentationType;
use App\Enums\Nova\NovaResourceType;
use App\Enums\Nova\NovaToolType;
use App\Models\Nova\NovaBinding;
use App\Models\Nova\NovaCapability;
use App\Models\Nova\NovaConnector;
use App\Models\Nova\NovaGroup;
use App\Models\Nova\NovaPanel;
use App\Models\Nova\NovaRelation;
use App\Models\Nova\NovaResource;
use App\Models\Nova\NovaTool;
use App\Models\Nova\NovaWorkspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class NovaStudioSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedCommunity();
            $this->seedProperty();
            $this->seedRent();
            $this->seedAccess();
            $this->seedBusiness();
        });
    }

    private function seedCommunity(): void
    {
        $workspace = $this->workspace('community', 'NOVA Community', 'Operación de comunidades, mantenimiento, propietarios y empleados.');
        $panel = $this->panel($workspace, 'community', 'NOVA Community', 10);

        $groups = [
            'property' => $this->group($panel, 'property', 'Propiedad', 10),
            'community' => $this->group($panel, 'community', 'Comunidad', 20),
            'maintenance' => $this->group($panel, 'maintenance', 'Mantenimiento', 30),
            'organization' => $this->group($panel, 'organization', 'Organización', 40),
            'access' => $this->group($panel, 'access', 'NOVA Access', 50),
        ];

        $resources = [
            'community' => $this->resource('community', 'Community', NovaResourceType::EloquentModel, 'App\\Models\\Community'),
            'property' => $this->resource('property', 'Property', NovaResourceType::EloquentModel, 'App\\Models\\Property'),
            'person' => $this->resource('person', 'Person', NovaResourceType::EloquentModel, 'App\\Models\\Person'),
            'employee' => $this->resource('employee', 'Employee', NovaResourceType::EloquentModel, 'App\\Models\\Employee'),
            'incident' => $this->resource('incident', 'Incident', NovaResourceType::EloquentModel, 'App\\Models\\Incident'),
            'work-order' => $this->resource('work-order', 'WorkOrder', NovaResourceType::EloquentModel, 'App\\Models\\WorkOrder'),
            'work-task' => $this->resource('work-task', 'WorkOrderTask', NovaResourceType::EloquentModel, 'App\\Models\\WorkOrderTask'),
            'plan' => $this->resource('community-plan', 'CommunityPlan', NovaResourceType::EloquentModel, 'App\\Models\\CommunityPlan'),
            'appointment' => $this->resource('community-appointment', 'CommunityAppointment', NovaResourceType::EloquentModel, 'App\\Models\\CommunityAppointment'),
            'ticket' => $this->resource('community-ticket', 'CommunityTicket', NovaResourceType::EloquentModel, 'App\\Models\\CommunityTicket'),
            'shift' => $this->resource('community-shift', 'CommunityShift', NovaResourceType::EloquentModel, 'App\\Models\\CommunityShift'),
            'attendance' => $this->resource('community-attendance', 'CommunityAttendance', NovaResourceType::EloquentModel, 'App\\Models\\CommunityAttendance'),
            'owner-document' => $this->resource('community-owner-document', 'CommunityOwnerDocument', NovaResourceType::EloquentModel, 'App\\Models\\CommunityOwnerDocument'),
            'employee-document' => $this->resource('community-employee-document', 'CommunityEmployeeDocument', NovaResourceType::EloquentModel, 'App\\Models\\CommunityEmployeeDocument'),
        ];

        $capabilities = [
            'properties' => $this->capability('community.properties', 'Propiedades', 'property', $resources, ['property', 'person'], [
                'view' => NovaToolType::View,
                'edit' => NovaToolType::Action,
                'documents' => NovaToolType::View,
            ]),
            'documents' => $this->capability('community.documents', 'Documentos', 'property', $resources, ['owner-document', 'employee-document'], [
                'view' => NovaToolType::View,
                'upload' => NovaToolType::Action,
                'download' => NovaToolType::Action,
            ]),
            'fees' => $this->capability('community.fees', 'Cuotas', 'property', $resources, ['property', 'person'], [
                'view' => NovaToolType::View,
                'download' => NovaToolType::Action,
            ]),
            'communities' => $this->capability('community.communities', 'Comunidades', 'community', $resources, ['community'], [
                'view' => NovaToolType::View,
            ]),
            'notices' => $this->capability('community.notices', 'Avisos', 'community', $resources, ['community', 'person'], [
                'view' => NovaToolType::View,
                'create' => NovaToolType::Action,
                'publish' => NovaToolType::Action,
            ]),
            'incidents' => $this->capability('community.incidents', 'Incidencias', 'community', $resources, ['incident', 'property', 'community', 'work-order'], [
                'view' => NovaToolType::View,
                'create' => NovaToolType::Action,
                'photo' => NovaToolType::Action,
                'priority' => NovaToolType::Action,
                'assign' => NovaToolType::Action,
                'resolve' => NovaToolType::Action,
            ]),
            'tickets' => $this->capability('community.tickets', 'Tickets', 'community', $resources, ['ticket', 'person', 'property'], [
                'view' => NovaToolType::View,
                'create' => NovaToolType::Action,
                'comment' => NovaToolType::Action,
                'convert-to-work-order' => NovaToolType::Workflow,
            ]),
            'appointments' => $this->capability('community.appointments', 'Citas', 'community', $resources, ['appointment', 'person', 'community'], [
                'view' => NovaToolType::View,
                'request' => NovaToolType::Action,
                'confirm' => NovaToolType::Action,
                'cancel' => NovaToolType::Action,
            ]),
            'plans' => $this->capability('community.plans', 'Planes', 'maintenance', $resources, ['plan', 'community'], [
                'view' => NovaToolType::View,
                'generate-orders' => NovaToolType::Workflow,
            ]),
            'work-orders' => $this->capability('community.work-orders', 'Órdenes', 'maintenance', $resources, ['work-order', 'community', 'employee'], [
                'view' => NovaToolType::View,
                'assign' => NovaToolType::Action,
                'start' => NovaToolType::Action,
                'complete' => NovaToolType::Action,
                'voice-summary' => NovaToolType::Action,
            ]),
            'tasks' => $this->capability('community.tasks', 'Tareas', 'maintenance', $resources, ['work-task', 'work-order'], [
                'view' => NovaToolType::View,
                'check' => NovaToolType::Action,
            ]),
            'shifts' => $this->capability('community.shifts', 'Turnos', 'organization', $resources, ['shift', 'employee', 'community', 'work-order'], [
                'view' => NovaToolType::View,
                'create' => NovaToolType::Action,
                'start' => NovaToolType::Action,
                'finish' => NovaToolType::Action,
            ]),
            'attendance' => $this->capability('community.attendance', 'Asistencia', 'organization', $resources, ['attendance', 'employee', 'community'], [
                'view' => NovaToolType::View,
                'register' => NovaToolType::Action,
                'geolocate' => NovaToolType::Action,
                'voice-summary' => NovaToolType::Action,
            ]),
            'expenses' => $this->capability('community.expenses', 'Gastos', 'maintenance', $resources, ['ticket', 'community', 'work-order'], [
                'view' => NovaToolType::View,
                'create' => NovaToolType::Action,
                'ocr' => NovaToolType::Action,
                'validate' => NovaToolType::Action,
            ]),
        ];

        $this->bindings($panel, $groups, $capabilities, [
            'owner' => [
                'livewire' => [
                    'property' => ['properties', 'documents', 'fees'],
                    'community' => ['notices', 'incidents', 'tickets', 'appointments'],
                ],
            ],
            'employee' => [
                'livewire' => [
                    'community' => ['communities', 'notices', 'incidents', 'tickets', 'appointments'],
                    'maintenance' => ['plans', 'work-orders', 'tasks', 'expenses'],
                    'organization' => ['shifts', 'attendance', 'documents'],
                ],
            ],
            'manager' => [
                'filament' => [
                    'property' => ['properties', 'documents', 'fees'],
                    'community' => ['communities', 'notices', 'incidents', 'tickets', 'appointments'],
                    'maintenance' => ['plans', 'work-orders', 'tasks', 'expenses'],
                    'organization' => ['shifts', 'attendance'],
                ],
            ],
        ]);

        $this->relation('community.properties', 'Community → Properties', $resources['community'], $resources['property'], NovaRelationType::HasMany, 'properties');
        $this->relation('property.community', 'Property → Community', $resources['property'], $resources['community'], NovaRelationType::BelongsTo, 'community');
        $this->relation('property.incidents', 'Property → Incidents', $resources['property'], $resources['incident'], NovaRelationType::HasMany, 'incidents');
        $this->relation('community.work-orders', 'Community → Work Orders', $resources['community'], $resources['work-order'], NovaRelationType::HasMany, 'workOrders');
        $this->relation('work-order.tasks', 'Work Order → Tasks', $resources['work-order'], $resources['work-task'], NovaRelationType::HasMany, 'tasks');
        $this->relation('work-order.incidents', 'Work Order → Incidents', $resources['work-order'], $resources['incident'], NovaRelationType::HasMany, 'incidents');

        $this->connector('whatsapp-community', 'WhatsApp Community', NovaConnectorType::WhatsApp, NovaConnectorDirection::Bidirectional, 'App\\Connectors\\WhatsApp');
        $this->connector('mcp-community', 'MCP Community', NovaConnectorType::Mcp, NovaConnectorDirection::Bidirectional, 'App\\Mcp');
    }

    private function seedProperty(): void
    {
        $workspace = $this->workspace('property', 'NOVA Property', 'Gestión canónica de inmuebles y personas.');
        $panel = $this->panel($workspace, 'property', 'NOVA Property', 20);
        $core = $this->group($panel, 'core', 'Propiedad', 10);

        $resources = [
            'property' => $this->resource('property', 'Property', NovaResourceType::EloquentModel, 'App\\Models\\Property'),
            'person' => $this->resource('person', 'Person', NovaResourceType::EloquentModel, 'App\\Models\\Person'),
        ];

        $capabilities = [
            'properties' => $this->capability('property.properties', 'Properties', 'core', $resources, ['property'], ['view'=>NovaToolType::View,'edit'=>NovaToolType::Action]),
            'owners' => $this->capability('property.owners', 'Owners', 'core', $resources, ['person','property'], ['view'=>NovaToolType::View,'edit'=>NovaToolType::Action]),
            'documents' => $this->capability('property.documents', 'Documents', 'core', $resources, ['property','person'], ['view'=>NovaToolType::View,'upload'=>NovaToolType::Action]),
            'tasks' => $this->capability('property.tasks', 'Tasks', 'core', $resources, ['property','person'], ['view'=>NovaToolType::View,'create'=>NovaToolType::Action]),
            'expenses' => $this->capability('property.expenses', 'Expenses', 'core', $resources, ['property'], ['view'=>NovaToolType::View,'create'=>NovaToolType::Action,'ocr'=>NovaToolType::Action]),
            'notes' => $this->capability('property.notes', 'Notes', 'core', $resources, ['property','person'], ['view'=>NovaToolType::View,'create'=>NovaToolType::Action]),
        ];

        $this->bindings($panel, ['core'=>$core], $capabilities, [
            'manager' => ['filament' => ['core' => array_keys($capabilities)]],
        ]);
    }

    private function seedRent(): void
    {
        $workspace = $this->workspace('rent', 'NOVA Rent', 'Operación de alquiler vacacional.');
        $panel = $this->panel($workspace, 'rent', 'NOVA Rent', 30);
        $rental = $this->group($panel, 'rental', 'Rental Operations', 10);

        $resources = [
            'property' => $this->resource('property', 'Property', NovaResourceType::EloquentModel, 'App\\Models\\Property'),
            'reservation' => $this->resource('rental-reservation', 'RentalReservation', NovaResourceType::EloquentModel, 'App\\Models\\RentalReservation'),
        ];

        $names = [
            'dashboard'=>'Dashboard',
            'properties'=>'Properties',
            'finances'=>'Finances',
            'contracts'=>'Contracts',
            'channels'=>'Channels',
            'calendar'=>'Calendar',
            'rates'=>'Rates',
            'reservations'=>'Reservations',
            'payments'=>'Payments',
        ];

        $caps = [];
        foreach ($names as $key=>$name) {
            $caps[$key] = $this->capability('rent.'.$key, $name, 'rental', $resources, $key === 'reservations' ? ['reservation','property'] : ['property'], [
                'view'=>NovaToolType::View,
                'manage'=>NovaToolType::Action,
            ]);
        }

        $this->bindings($panel, ['rental'=>$rental], $caps, [
            'manager' => ['filament' => ['rental' => array_keys($caps)]],
        ]);
    }

    private function seedAccess(): void
    {
        $workspace = $this->workspace('access', 'NOVA Access', 'Control de acceso físico.');
        $panel = $this->panel($workspace, 'access', 'NOVA Access', 40);
        $group = $this->group($panel, 'access', 'Access', 10);

        $resources = [
            'property' => $this->resource('property', 'Property', NovaResourceType::EloquentModel, 'App\\Models\\Property'),
            'person' => $this->resource('person', 'Person', NovaResourceType::EloquentModel, 'App\\Models\\Person'),
            'credential' => $this->resource('credential', 'Credential', NovaResourceType::EloquentModel, 'App\\Models\\Credential'),
            'grant' => $this->resource('access-grant', 'AccessGrant', NovaResourceType::EloquentModel, 'App\\Models\\AccessGrant'),
            'point' => $this->resource('access-point', 'AccessPoint', NovaResourceType::EloquentModel, 'App\\Models\\AccessPoint'),
            'device' => $this->resource('device', 'Device', NovaResourceType::EloquentModel, 'App\\Models\\Device'),
            'event' => $this->resource('access-event', 'AccessEvent', NovaResourceType::EloquentModel, 'App\\Models\\DomoticsEvent'),
        ];

        $defs = [
            'properties'=>['Properties',['property']],
            'people'=>['People',['person']],
            'credentials'=>['Credentials',['credential','person']],
            'grants'=>['Access Grants',['grant','person','property']],
            'points'=>['Access Points',['point','property']],
            'devices'=>['Devices',['device','property']],
            'events'=>['Access Events',['event','property']],
        ];

        $caps = [];
        foreach ($defs as $key=>[$name,$resourceKeys]) {
            $caps[$key] = $this->capability('access.'.$key, $name, 'access', $resources, $resourceKeys, [
                'view'=>NovaToolType::View,
                'manage'=>NovaToolType::Action,
            ]);
        }

        $this->bindings($panel, ['access'=>$group], $caps, [
            'manager' => ['filament' => ['access' => array_keys($caps)]],
        ]);

        $this->connector('access-vendor-api', 'Access Vendor API', NovaConnectorType::Rest, NovaConnectorDirection::Bidirectional, 'App\\Domain\\Access');
    }

    private function seedBusiness(): void
    {
        $workspace = $this->workspace('business', 'NOVA Business', 'Capacidades empresariales reutilizables.');
        $panel = $this->panel($workspace, 'business', 'NOVA Business', 50);

        $groups = [
            'invoice'=>$this->group($panel, 'invoice', 'Invoice', 10),
            'calendar'=>$this->group($panel, 'calendar', 'Calendar', 20),
            'docs'=>$this->group($panel, 'docs', 'Docs', 30),
            'hr'=>$this->group($panel, 'hr', 'HR', 40),
            'shop'=>$this->group($panel, 'shop', 'Shop', 50),
        ];

        $resource = $this->resource('person', 'Person', NovaResourceType::EloquentModel, 'App\\Models\\Person');

        $caps = [];
        foreach ([
            'invoice'=>['companies','customers','invoices','services','expenses'],
            'calendar'=>['service-calendar','appointments','reminders'],
            'docs'=>['documents','notes','tickets'],
            'hr'=>['users','roles','employees','departments','employee-documents','appointments','tickets','tasks','projects'],
            'shop'=>['company','products','categories','orders','payments','shipping','customers','pricing','catalog','checkout','campaigns','pages','menus','connectors'],
        ] as $groupKey=>$items) {
            foreach ($items as $item) {
                $caps[$groupKey.'.'.$item] = $this->capability(
                    'business.'.$groupKey.'.'.$item,
                    str($item)->replace('-', ' ')->headline()->toString(),
                    $groupKey,
                    ['person'=>$resource],
                    ['person'],
                    ['view'=>NovaToolType::View,'manage'=>NovaToolType::Action]
                );
            }
        }

        $bindingMap = [];
        foreach ($groups as $groupKey=>$group) {
            $bindingMap[$groupKey] = collect(array_keys($caps))
                ->filter(fn (string $key): bool => str_starts_with($key, $groupKey.'.'))
                ->map(fn (string $key): string => $key)
                ->values()
                ->all();
        }

        $this->bindings($panel, $groups, $caps, [
            'manager' => ['filament' => $bindingMap],
        ]);

        $this->connector('woocommerce', 'WooCommerce', NovaConnectorType::WooCommerce, NovaConnectorDirection::Bidirectional, null);
        $this->connector('magento', 'Magento 2', NovaConnectorType::Magento, NovaConnectorDirection::Bidirectional, null);
        $this->connector('wordpress', 'WordPress', NovaConnectorType::WordPress, NovaConnectorDirection::Bidirectional, null);
        $this->connector('latepoint', 'LatePoint', NovaConnectorType::LatePoint, NovaConnectorDirection::Bidirectional, null);
        $this->connector('mcp-business', 'MCP Business', NovaConnectorType::Mcp, NovaConnectorDirection::Bidirectional, null);
    }

    private function workspace(string $key, string $name, string $description): NovaWorkspace
    {
        return NovaWorkspace::query()->updateOrCreate(
            ['key'=>$key],
            ['name'=>$name,'description'=>$description,'status'=>'active','settings'=>[]],
        );
    }

    private function panel(NovaWorkspace $workspace, string $key, string $name, int $sort): NovaPanel
    {
        return NovaPanel::query()->updateOrCreate(
            ['workspace_id'=>$workspace->id,'key'=>$key],
            ['name'=>$name,'sort'=>$sort,'settings'=>[]],
        );
    }

    private function group(NovaPanel $panel, string $key, string $name, int $sort): NovaGroup
    {
        return NovaGroup::query()->updateOrCreate(
            ['panel_id'=>$panel->id,'key'=>$key],
            ['name'=>$name,'sort'=>$sort,'settings'=>[]],
        );
    }

    private function resource(string $key, string $name, NovaResourceType $type, ?string $className): NovaResource
    {
        return NovaResource::query()->updateOrCreate(
            ['key'=>$key],
            ['name'=>$name,'type'=>$type,'class_name'=>$className,'settings'=>[]],
        );
    }

    /**
     * @param array<string, NovaResource> $resources
     * @param array<int, string> $resourceKeys
     * @param array<string, NovaToolType> $tools
     */
    private function capability(string $key, string $name, string $group, array $resources, array $resourceKeys, array $tools): NovaCapability
    {
        $capability = NovaCapability::query()->updateOrCreate(
            ['key'=>$key],
            ['name'=>$name,'status'=>'active','settings'=>['group'=>$group]],
        );

        foreach ($tools as $toolKey=>$type) {
            NovaTool::query()->updateOrCreate(
                ['capability_id'=>$capability->id,'key'=>$toolKey],
                ['name'=>str($toolKey)->replace('-', ' ')->headline()->toString(),'type'=>$type,'settings'=>[]],
            );
        }

        $sync = [];
        foreach ($resourceKeys as $index=>$resourceKey) {
            if (isset($resources[$resourceKey])) {
                $sync[$resources[$resourceKey]->id] = ['sort'=>($index+1)*10,'settings'=>'{}'];
            }
        }
        if ($sync !== []) {
            $capability->resources()->syncWithoutDetaching($sync);
        }

        return $capability;
    }

    /**
     * @param array<string, NovaGroup> $groups
     * @param array<string, NovaCapability> $capabilities
     * @param array<string, array<string, array<string, array<int, string>>>> $map
     */
    private function bindings(NovaPanel $panel, array $groups, array $capabilities, array $map): void
    {
        foreach ($map as $role=>$representations) {
            foreach ($representations as $representation=>$groupMap) {
                $sort = 10;

                foreach ($groupMap as $groupKey=>$capabilityKeys) {
                    foreach ($capabilityKeys as $capabilityKey) {
                        $capability = $capabilities[$capabilityKey] ?? null;
                        $group = $groups[$groupKey] ?? null;

                        if (! $capability || ! $group) {
                            continue;
                        }

                        NovaBinding::query()->updateOrCreate(
                            [
                                'panel_id'=>$panel->id,
                                'capability_id'=>$capability->id,
                                'target_type'=>NovaBindingTarget::Capability,
                                'role'=>$role,
                                'representation'=>NovaRepresentationType::from($representation),
                            ],
                            [
                                'group_id'=>$group->id,
                                'visible'=>true,
                                'sort'=>$sort,
                                'settings'=>['label'=>$capability->name],
                            ],
                        );

                        $sort += 10;
                    }
                }
            }
        }
    }

    private function relation(
        string $key,
        string $name,
        NovaResource $source,
        NovaResource $target,
        NovaRelationType $type,
        ?string $relationName = null
    ): NovaRelation {
        return NovaRelation::query()->updateOrCreate(
            ['key'=>$key],
            [
                'name'=>$name,
                'source_resource_id'=>$source->id,
                'target_resource_id'=>$target->id,
                'type'=>$type,
                'relation_name'=>$relationName,
                'settings'=>[],
            ],
        );
    }

    private function connector(
        string $key,
        string $name,
        NovaConnectorType $type,
        NovaConnectorDirection $direction,
        ?string $adapter
    ): NovaConnector {
        return NovaConnector::query()->updateOrCreate(
            ['key'=>$key],
            [
                'name'=>$name,
                'type'=>$type,
                'direction'=>$direction,
                'adapter'=>$adapter,
                'status'=>'active',
                'settings'=>[],
            ],
        );
    }
}
