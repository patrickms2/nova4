# Nova Domotics — Filament Blueprint

`Using Filament Blueprint`

Documentos de Blueprint leídos:
- `docs/filament-blueprint/SKILL.md`
- `vendor/filament/blueprint/resources/boost/guidelines/core.blade.php`
- `vendor/filament/blueprint/resources/markdown/planning/overview.md`
- `vendor/filament/blueprint/resources/markdown/planning/models.md`
- `vendor/filament/blueprint/resources/markdown/planning/resources.md`
- `vendor/filament/blueprint/resources/markdown/planning/forms.md`
- `vendor/filament/blueprint/resources/markdown/planning/tables.md`
- `vendor/filament/blueprint/resources/markdown/planning/actions.md`
- `vendor/filament/blueprint/resources/markdown/planning/authorization.md`
- `vendor/filament/blueprint/resources/markdown/planning/multi-tenancy.md`
- `vendor/filament/blueprint/resources/markdown/planning/widgets.md`
- `vendor/filament/blueprint/resources/markdown/planning/testing.md`
- `vendor/filament/blueprint/resources/markdown/planning/checklist.md`
- `docs/filament-forms-ux-audit/SKILL.md` (para criterios de formularios)

## Alcance confirmado

Primer blueprint del módulo **Domotics** en Nova, cubriendo el núcleo Laravel/Filament:

- Fase 1: Propiedades / Instalaciones (tenant), Dispositivos.
- Fase 2: Puntos de acceso (portón, puerta, garaje), PINs / Invitados, registro de eventos.
- Fase 3: Automatizaciones (reglas if-this-then-that).
- Caso de uso extremo a extremo: **apertura de portón con PIN** (validación, comando al dispositivo, registro de evento).

Queda fuera de este blueprint: configuración de Docker/Ubuntu/MQTT/Home Assistant en el edge; se tratarán como frontera de integración (API/MQTT) en fases posteriores.

## Decisiones cerradas

1. **Multi-tenancy**: Filament multi-tenant desde el día 1. Tenant = `Property` (Propiedad/Instalación). Todos los recursos del panel Domotics están scopped por tenant.
2. **Panel**: Nuevo `DomoticsPanelProvider` dedicado en `/domotics`. El `AdminPanelProvider` existente ya está saturado.
3. **Autorización**: `spatie/laravel-permission` (ya disponible en `composer.lock` `^8.3`). Roles: `owner`, `admin`, `guest`, `technician`.
4. **PINs e Invitados**: PIN vinculado a `AccessGrant` con `valid_from`/`valid_until` y relación `belongsToMany` a `AccessPoint`. En alquiler turístico se puede vincular el grant a una `Booking`/`Reservation` existente (campo `booking_id` opcional).

---

## 1. Commands

Scaffold inicial (ejecutar en orden):

```bash
php artisan make:model Property --migration --factory --no-interaction
php artisan make:model Device --migration --factory --no-interaction
php artisan make:model AccessPoint --migration --factory --no-interaction
php artisan make:model AccessGrant --migration --factory --no-interaction
php artisan make:model Automation --migration --factory --no-interaction
php artisan make:model AutomationCondition --migration --no-interaction
php artisan make:model AutomationAction --migration --factory --no-interaction
php artisan make:model DomoticsEvent --migration --factory --no-interaction

php artisan make:filament-resource Property --view --no-interaction
php artisan make:filament-resource Device --view --no-interaction
php artisan make:filament-resource AccessPoint --view --no-interaction
php artisan make:filament-resource AccessGrant --view --no-interaction
php artisan make:filament-resource Automation --view --no-interaction
php artisan make:filament-resource DomoticsEvent --view --no-interaction

php artisan make:filament-panel Domotics --no-interaction
php artisan make:policy Property --model=Property --no-interaction
php artisan make:policy Device --model=Device --no-interaction
php artisan make:policy AccessPoint --model=AccessPoint --no-interaction
php artisan make:policy AccessGrant --model=AccessGrant --no-interaction
php artisan make:policy Automation --model=Automation --no-interaction
php artisan make:policy DomoticsEvent --model=DomoticsEvent --no-interaction

php artisan make:command GenerateAccessPin --no-interaction
```

---

## 2. Enums

Enum: `App\Enums\DeviceType`
  Implements: `Filament\Support\Contracts\HasLabel`, `Filament\Support\Contracts\HasColor`
  Cases:
    - lock: label "Cerradura", color "primary"
    - sensor: label "Sensor", color "info"
    - camera: label "Cámara", color "warning"
    - light: label "Iluminación", color "warning"
    - thermostat: label "Termostato", color "danger"
    - hub: label "Hub", color "success"
    - other: label "Otro", color "gray"

Enum: `App\Enums\DeviceStatus`
  Implements: `Filament\Support\Contracts\HasLabel`, `Filament\Support\Contracts\HasColor`
  Cases:
    - online: label "Online", color "success"
    - offline: label "Offline", color "danger"
    - unknown: label "Desconocido", color "gray"

Enum: `App\Enums\AccessPointType`
  Implements: `Filament\Support\Contracts\HasLabel`
  Cases:
    - gate: label "Portón"
    - door: label "Puerta"
    - garage: label "Garaje"
    - pedestrian_door: label "Puerta peatonal"
    - other: label "Otro"

Enum: `App\Enums\DomoticsEventType`
  Implements: `Filament\Support\Contracts\HasLabel`, `Filament\Support\Contracts\HasColor`
  Cases:
    - access_granted: label "Acceso concedido", color "success"
    - access_denied: label "Acceso denegado", color "danger"
    - device_online: label "Dispositivo online", color "success"
    - device_offline: label "Dispositivo offline", color "danger"
    - automation_triggered: label "Automatización ejecutada", color "info"
    - sensor_reading: label "Lectura de sensor", color "gray"

---

## 3. Models

### Model: Property (tenant)
  Table: properties
  Attributes:
    - id: bigint, primary
    - slug: string, unique, required
    - name: string, required
    - address: text, nullable
    - timezone: string, default:'Atlantic/Canary'
    - owner_id: bigint, foreign(users.id), required
    - settings: json, nullable
    - is_active: boolean, default:true
    - created_at: timestamp
    - updated_at: timestamp
  Relationships:
    - belongsTo: User (owner) via owner_id
    - belongsToMany: User (members) via property_user pivot
    - hasMany: Device via property_id
    - hasMany: AccessPoint via property_id
    - hasMany: AccessGrant via property_id
    - hasMany: Automation via property_id
    - hasMany: DomoticsEvent via property_id
  Traits:
    - HasUniqueSlug (o implementar `getRouteKeyName` / `resolveRouteBinding` con slug)
  Notes:
    - Actúa como tenant en Filament; `slug` se usa en URLs (`/domotics/{tenant}`).

### Model: Device
  Table: devices
  Attributes:
    - id: bigint, primary
    - property_id: bigint, foreign(properties.id), required
    - name: string, required
    - type: enum(App\Enums\DeviceType), required
    - identifier: string, required
    - status: enum(App\Enums\DeviceStatus), default:unknown
    - meta: json, nullable
    - last_seen_at: timestamp, nullable
    - created_at: timestamp
    - updated_at: timestamp
  Relationships:
    - belongsTo: Property via property_id
    - hasMany: AccessPoint via device_id
    - hasMany: DomoticsEvent via device_id
  Constraints:
    - unique combinado: property_id + identifier
  Notes:
    - `identifier` es el ID físico o de integración (MAC, serial, entity_id de HA, etc.).

### Model: AccessPoint
  Table: access_points
  Attributes:
    - id: bigint, primary
    - property_id: bigint, foreign(properties.id), required
    - device_id: bigint, foreign(devices.id), nullable
    - name: string, required
    - type: enum(App\Enums\AccessPointType), required
    - location: string, nullable
    - is_active: boolean, default:true
    - created_at: timestamp
    - updated_at: timestamp
  Relationships:
    - belongsTo: Property via property_id
    - belongsTo: Device via device_id
    - belongsToMany: AccessGrant via access_grant_access_point pivot
    - hasMany: DomoticsEvent via access_point_id

### Model: AccessGrant
  Table: access_grants
  Attributes:
    - id: bigint, primary
    - property_id: bigint, foreign(properties.id), required
    - user_id: bigint, foreign(users.id), nullable
    - booking_id: bigint, nullable (reserva externa; sin FK forzada por ahora)
    - name: string, required
    - pin: string, nullable, unique por property
    - valid_from: timestamp, nullable
    - valid_until: timestamp, nullable
    - is_active: boolean, default:true
    - created_by: bigint, foreign(users.id), nullable
    - created_at: timestamp
    - updated_at: timestamp
  Relationships:
    - belongsTo: Property via property_id
    - belongsTo: User via user_id
    - belongsTo: User (creator) via created_by
    - belongsToMany: AccessPoint via access_grant_access_point pivot
    - hasMany: DomoticsEvent via access_grant_id
  Constraints:
    - unique: property_id + pin
    - check: valid_until > valid_from (cuando ambos no sean null)
  Notes:
    - Si `user_id` es null, el grant es anónimo (PIN suelto para invitado temporal).
    - El PIN debe generarse automáticamente si el usuario no lo introduce (4-6 dígitos).

### Model: Automation
  Table: automations
  Attributes:
    - id: bigint, primary
    - property_id: bigint, foreign(properties.id), required
    - name: string, required
    - is_active: boolean, default:true
    - created_at: timestamp
    - updated_at: timestamp
  Relationships:
    - belongsTo: Property via property_id
    - hasMany: AutomationCondition via automation_id
    - hasMany: AutomationAction via automation_id

### Model: AutomationCondition
  Table: automation_conditions
  Attributes:
    - id: bigint, primary
    - automation_id: bigint, foreign(automations.id), required
    - type: string, required (device_state|time_range|sensor_value)
    - source_id: bigint, nullable (device o access_point según type)
    - source_type: string, nullable (polimórfico: Device|AccessPoint)
    - operator: string, nullable (eq|gt|lt|between)
    - value: string, nullable
    - created_at: timestamp
    - updated_at: timestamp
  Relationships:
    - belongsTo: Automation via automation_id
    - morphTo: source (Device|AccessPoint)

### Model: AutomationAction
  Table: automation_actions
  Attributes:
    - id: bigint, primary
    - automation_id: bigint, foreign(automations.id), required
    - type: string, required (open_access_point|close_access_point|notify|webhook)
    - target_id: bigint, nullable
    - target_type: string, nullable (polimórfico: AccessPoint)
    - payload: json, nullable
    - sort: integer, default:0
    - created_at: timestamp
    - updated_at: timestamp
  Relationships:
    - belongsTo: Automation via automation_id
    - morphTo: target (AccessPoint)

### Model: DomoticsEvent
  Table: domotics_events
  Attributes:
    - id: bigint, primary
    - property_id: bigint, foreign(properties.id), required
    - device_id: bigint, foreign(devices.id), nullable
    - access_point_id: bigint, foreign(access_points.id), nullable
    - access_grant_id: bigint, foreign(access_grants.id), nullable
    - user_id: bigint, foreign(users.id), nullable
    - event_type: enum(App\Enums\DomoticsEventType), required
    - payload: json, nullable
    - created_at: timestamp
  Relationships:
    - belongsTo: Property via property_id
    - belongsTo: Device via device_id
    - belongsTo: AccessPoint via access_point_id
    - belongsTo: AccessGrant via access_grant_id
    - belongsTo: User via user_id
  Notes:
    - Tabla de audit log; sin `updated_at` para optimizar inserciones masivas.

### Update Existing Model: User
  Migration: add_domotics_fields_to_users
  Add: current_property_id: bigint, foreign(properties.id), nullable
  Add: belongsToMany properties pivot (property_user) con timestamps y role pivot (string, nullable)
  Changes:
    - Implements: `Filament\Models\Contracts\HasTenants`
    - Method: getTenants(Panel $panel): Collection  // returns $this->properties
    - Method: canAccessTenant(Model $tenant): bool  // returns $this->properties->contains($tenant)
    - Remove/replace: `canAccessTenant` actual que siempre retorna `true`.

---

## 4. Panel

Panel: DomoticsPanelProvider
  Location: `App\Providers\Filament\DomoticsPanelProvider`
  Docs: https://filamentphp.com/docs/5.x/panels/configuration
  Config:
    - id('domotics')
    - path('/domotics')
    - login()
    - colors(['primary' => Color::Emerald])
    - tenant(Property::class, slugAttribute: 'slug')
    - tenantRegistration(false)  // Las properties se crean desde AdminPanel o por CLI; no auto-registro público
    - tenantMiddleware([App\Http\Middleware\ApplyTenantScopes::class])
    - discoverResources(in: app_path('Filament/Domotics/Resources'), for: 'App\\Filament\\Domotics\\Resources')
    - widgets([
        App\Filament\Domotics\Widgets\DeviceStatsWidget::class,
        App\Filament\Domotics\Widgets\RecentEventsWidget::class,
      ])
  Navigation Groups:
    - "Instalación": Property, Device, AccessPoint
    - "Accesos": AccessGrant
    - "Automatización": Automation
    - "Registro": DomoticsEvent

---

## 5. Resources

### Resource: PropertyResource
  Command: php artisan make:filament-resource Property --view --no-interaction
  Location: `App\Filament\Domotics\Resources\PropertyResource`
  Docs: https://filamentphp.com/docs/5.x/resources/getting-started
  Navigation:
    Group: Instalación
    Icon: Heroicon::Home
    Sort: 1
  RecordTitleAttribute: name

  Form:
    Columns: 1
    Section: Datos básicos
      Field: name
        Component: Filament\Forms\Components\TextInput
        Docs: https://filamentphp.com/docs/5.x/forms/text-input
        Validation: required, max:255
        Config: ->label('Nombre'), ->placeholder('Villa Norte')
      Field: slug
        Component: Filament\Forms\Components\TextInput
        Docs: https://filamentphp.com/docs/5.x/forms/text-input
        Validation: required, max:255, unique:properties,slug
        Config: ->label('Slug'), ->placeholder('villa-norte'), ->helperText('Usado en la URL del tenant.')
      Field: address
        Component: Filament\Forms\Components\Textarea
        Docs: https://filamentphp.com/docs/5.x/forms/textarea
        Validation: nullable, max:1000
        Config: ->label('Dirección'), ->rows(3)
      Field: timezone
        Component: Filament\Forms\Components\Select
        Docs: https://filamentphp.com/docs/5.x/forms/select
        Validation: required
        Config: ->label('Zona horaria'), ->options(DateTimeZone::listIdentifiers()), ->searchable(), ->default('Atlantic/Canary')
      Field: owner_id
        Component: Filament\Forms\Components\Select
        Docs: https://filamentphp.com/docs/5.x/forms/select
        Validation: required
        Config: ->label('Propietario'), ->relationship('owner', 'email'), ->searchable(), ->preload()
      Field: is_active
        Component: Filament\Forms\Components\Toggle
        Docs: https://filamentphp.com/docs/5.x/forms/toggle
        Validation: nullable
        Config: ->label('Activa')

  Table:
    Column: name
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->label('Nombre'), ->searchable(), ->sortable()
    Column: slug
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->label('Slug'), ->searchable()
    Column: owner.email
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->label('Propietario')
    Column: is_active
      Component: Filament\Tables\Columns\IconColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/icon
      Config: ->label('Activa'), ->boolean()
    Column: created_at
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->label('Creada'), ->dateTime(), ->sortable()

  Actions:
    Action: ManageMembers
      Component: Filament\Actions\Action
      Docs: https://filamentphp.com/docs/5.x/actions/overview
      Location: table row
      Icon: Heroicon::Users
      Color: primary
      Authorization: user has 'manage property members' permission
      Behavior:
        - Redirige a `PropertyResource::getUrl('members', ['tenant' => $record])`

---

### Resource: DeviceResource
  Command: php artisan make:filament-resource Device --view --no-interaction
  Location: `App\Filament\Domotics\Resources\DeviceResource`
  Navigation:
    Group: Instalación
    Icon: Heroicon::CpuChip
    Sort: 2

  Form:
    Columns: 2
    Field: name
      Component: Filament\Forms\Components\TextInput
      Docs: https://filamentphp.com/docs/5.x/forms/text-input
      Validation: required, max:255
      Config: ->label('Nombre')
    Field: type
      Component: Filament\Forms\Components\Select
      Docs: https://filamentphp.com/docs/5.x/forms/select
      Validation: required
      Config: ->label('Tipo'), ->options(App\Enums\DeviceType::class)
    Field: identifier
      Component: Filament\Forms\Components\TextInput
      Docs: https://filamentphp.com/docs/5.x/forms/text-input
      Validation: required, max:255, scopedUnique:devices,identifier
      Config: ->label('Identificador físico'), ->helperText('Serial, MAC o entity_id de Home Assistant')
    Field: status
      Component: Filament\Forms\Components\Select
      Docs: https://filamentphp.com/docs/5.x/forms/select
      Validation: required
      Config: ->label('Estado'), ->options(App\Enums\DeviceStatus::class), ->default('unknown')
    Field: meta
      Component: Filament\Forms\Components\KeyValue
      Docs: https://filamentphp.com/docs/5.x/forms/key-value
      Validation: nullable
      Config: ->label('Metadatos'), ->keyLabel('Clave')->valueLabel('Valor')
    Field: last_seen_at
      Component: Filament\Forms\Components\DateTimePicker
      Docs: https://filamentphp.com/docs/5.x/forms/date-time-picker
      Validation: nullable
      Config: ->label('Última conexión'), ->hiddenOn('create')

  Table:
    Column: name
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->label('Nombre'), ->searchable(), ->sortable()
    Column: type
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->label('Tipo'), ->badge()
    Column: identifier
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->label('Identificador'), ->searchable(), ->copyable()
    Column: status
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->label('Estado'), ->badge()
    Column: last_seen_at
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->label('Última conexión'), ->dateTime(), ->sortable()

  Filters:
    Filter: type
      Component: Filament\Tables\Filters\SelectFilter
      Docs: https://filamentphp.com/docs/5.x/tables/filters/select
      Config: ->options(App\Enums\DeviceType::class), ->multiple()
    Filter: status
      Component: Filament\Tables\Filters\SelectFilter
      Docs: https://filamentphp.com/docs/5.x/tables/filters/select
      Config: ->options(App\Enums\DeviceStatus::class), ->multiple()

---

### Resource: AccessPointResource
  Command: php artisan make:filament-resource AccessPoint --view --no-interaction
  Location: `App\Filament\Domotics\Resources\AccessPointResource`
  Navigation:
    Group: Instalación
    Icon: Heroicon::DoorOpen
    Sort: 3

  Form:
    Columns: 2
    Field: name
      Component: Filament\Forms\Components\TextInput
      Docs: https://filamentphp.com/docs/5.x/forms/text-input
      Validation: required, max:255
      Config: ->label('Nombre'), ->placeholder('Portón principal')
    Field: type
      Component: Filament\Forms\Components\Select
      Docs: https://filamentphp.com/docs/5.x/forms/select
      Validation: required
      Config: ->label('Tipo'), ->options(App\Enums\AccessPointType::class)
    Field: device_id
      Component: Filament\Forms\Components\Select
      Docs: https://filamentphp.com/docs/5.x/forms/select
      Validation: nullable
      Config: ->label('Dispositivo asociado'), ->relationship('device', 'name'), ->searchable(), ->preload(), ->helperText('Dispositivo que acciona este punto de acceso')
    Field: location
      Component: Filament\Forms\Components\TextInput
      Docs: https://filamentphp.com/docs/5.x/forms/text-input
      Validation: nullable, max:255
      Config: ->label('Ubicación')
    Field: is_active
      Component: Filament\Forms\Components\Toggle
      Docs: https://filamentphp.com/docs/5.x/forms/toggle
      Validation: nullable
      Config: ->label('Activo')

  Table:
    Column: name
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->label('Nombre'), ->searchable(), ->sortable()
    Column: type
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->label('Tipo'), ->badge()
    Column: device.name
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->label('Dispositivo')
    Column: is_active
      Component: Filament\Tables\Columns\IconColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/icon
      Config: ->label('Activo'), ->boolean()

  Actions:
    Action: Open
      Component: Filament\Actions\Action
      Docs: https://filamentphp.com/docs/5.x/actions/overview
      Location: table row
      Icon: Heroicon::LockOpen
      Color: success
      Authorization: user has 'open access points' permission
      Confirmation: "¿Abrir este punto de acceso?"
      Behavior:
        - Dispatch `OpenAccessPoint` job/command
        - Create `DomoticsEvent` type `access_granted` with `user_id = auth()->id()`
      Notification: "Orden de apertura enviada"

---

### Resource: AccessGrantResource
  Command: php artisan make:filament-resource AccessGrant --view --no-interaction
  Location: `App\Filament\Domotics\Resources\AccessGrantResource`
  Navigation:
    Group: Accesos
    Icon: Heroicon::Key
    Sort: 1

  Form:
    Columns: 2
    Field: name
      Component: Filament\Forms\Components\TextInput
      Docs: https://filamentphp.com/docs/5.x/forms/text-input
      Validation: required, max:255
      Config: ->label('Nombre'), ->placeholder('Familia García - julio')
    Field: user_id
      Component: Filament\Forms\Components\Select
      Docs: https://filamentphp.com/docs/5.x/forms/select
      Validation: nullable
      Config: ->label('Usuario vinculado'), ->relationship('user', 'email'), ->searchable(), ->preload(), ->helperText('Opcional. Si se deja vacío, el PIN es anónimo.')
    Field: pin
      Component: Filament\Forms\Components\TextInput
      Docs: https://filamentphp.com/docs/5.x/forms/text-input
      Validation: nullable, digits_between:4,6, scopedUnique:access_grants,pin
      Config: ->label('PIN'), ->placeholder('Generado automáticamente'), ->helperText('Déjelo vacío para generar uno aleatorio de 4 dígitos.'), ->numeric(false)
    Field: access_points
      Component: Filament\Forms\Components\Select
      Docs: https://filamentphp.com/docs/5.x/forms/select
      Validation: required, array, min:1
      Config: ->label('Puntos de acceso permitidos'), ->relationship('accessPoints', 'name'), ->multiple(), ->preload(), ->searchable()
    Field: valid_from
      Component: Filament\Forms\Components\DateTimePicker
      Docs: https://filamentphp.com/docs/5.x/forms/date-time-picker
      Validation: nullable
      Config: ->label('Válido desde')
    Field: valid_until
      Component: Filament\Forms\Components\DateTimePicker
      Docs: https://filamentphp.com/docs/5.x/forms/date-time-picker
      Validation: nullable, after_or_equal:valid_from
      Config: ->label('Válido hasta')
    Field: is_active
      Component: Filament\Forms\Components\Toggle
      Docs: https://filamentphp.com/docs/5.x/forms/toggle
      Validation: nullable
      Config: ->label('Activo')

  Reactive Fields:
    Imports: Filament\Schemas\Components\Utilities\Get, Filament\Schemas\Components\Utilities\Set
    Behavior:
      - When `pin` is empty on create, auto-generate 4-digit PIN via `GenerateAccessPin` command logic.

  Table:
    Column: name
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->label('Nombre'), ->searchable(), ->sortable()
    Column: pin
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->label('PIN'), ->copyable(), ->fontFamily('mono')
    Column: user.email
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->label('Usuario'), ->placeholder('Anónimo')
    Column: accessPoints.name
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->label('Puntos'), ->badge(), ->limit(40)
    Column: valid_until
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->label('Válido hasta'), ->dateTime(), ->sortable()
    Column: is_active
      Component: Filament\Tables\Columns\IconColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/icon
      Config: ->label('Activo'), ->boolean()

  Filters:
    Filter: is_active
      Component: Filament\Tables\Filters\TernaryFilter
      Docs: https://filamentphp.com/docs/5.x/tables/filters/ternary
      Config: ->label('Solo activos')
    Filter: access_points
      Component: Filament\Tables\Filters\SelectFilter
      Docs: https://filamentphp.com/docs/5.x/tables/filters/select
      Config: ->relationship('accessPoints', 'name'), ->multiple()

  Actions:
    Action: RegeneratePin
      Component: Filament\Actions\Action
      Docs: https://filamentphp.com/docs/5.x/actions/overview
      Location: table row
      Icon: Heroicon::ArrowPath
      Color: warning
      Authorization: user has 'manage access grants' permission
      Confirmation: "¿Generar un nuevo PIN? El anterior dejará de funcionar."
      Behavior:
        - Generate new 4-digit PIN, scoped unique within property
        - Save and notify if user_id is set
      Notification: "PIN regenerado"

---

### Resource: AutomationResource
  Command: php artisan make:filament-resource Automation --view --no-interaction
  Location: `App\Filament\Domotics\Resources\AutomationResource`
  Navigation:
    Group: Automatización
    Icon: Heroicon::Bolt
    Sort: 1

  Form:
    Columns: 1
    Field: name
      Component: Filament\Forms\Components\TextInput
      Docs: https://filamentphp.com/docs/5.x/forms/text-input
      Validation: required, max:255
      Config: ->label('Nombre')
    Field: is_active
      Component: Filament\Forms\Components\Toggle
      Docs: https://filamentphp.com/docs/5.x/forms/toggle
      Validation: nullable
      Config: ->label('Activa')
    Section: Condiciones
      Component: Filament\Forms\Components\Repeater
      Docs: https://filamentphp.com/docs/5.x/forms/repeater
      Config: ->relationship('conditions'), ->schema([...])
      Field: type
        Component: Filament\Forms\Components\Select
        Docs: https://filamentphp.com/docs/5.x/forms/select
        Validation: required
        Config: ->label('Tipo'), ->options(['device_state' => 'Estado de dispositivo', 'time_range' => 'Rango horario', 'sensor_value' => 'Valor de sensor'])
      Field: source
        Component: Filament\Forms\Components\Select
        Docs: https://filamentphp.com/docs/5.x/forms/select
        Validation: nullable
        Config: ->label('Origen'), ->options(fn () => [...])  // TODO: depende de type
    Section: Acciones
      Component: Filament\Forms\Components\Repeater
      Docs: https://filamentphp.com/docs/5.x/forms/repeater
      Config: ->relationship('actions'), ->schema([...])
      Field: type
        Component: Filament\Forms\Components\Select
        Docs: https://filamentphp.com/docs/5.x/forms/select
        Validation: required
        Config: ->label('Tipo'), ->options(['open_access_point' => 'Abrir punto de acceso', 'close_access_point' => 'Cerrar punto de acceso', 'notify' => 'Notificar', 'webhook' => 'Webhook'])
      Field: target
        Component: Filament\Forms\Components\Select
        Docs: https://filamentphp.com/docs/5.x/forms/select
        Validation: nullable
        Config: ->label('Objetivo'), ->relationship('target', 'name')

  Table:
    Column: name
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->label('Nombre'), ->searchable(), ->sortable()
    Column: is_active
      Component: Filament\Tables\Columns\IconColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/icon
      Config: ->label('Activa'), ->boolean()

---

### Resource: DomoticsEventResource
  Command: php artisan make:filament-resource DomoticsEvent --view --no-interaction
  Location: `App\Filament\Domotics\Resources\DomoticsEventResource`
  Navigation:
    Group: Registro
    Icon: Heroicon::ClipboardDocumentList
    Sort: 1
  RecordTitleAttribute: id

  Form: read-only (no create/edit from Filament; events are created by commands/jobs)

  Table:
    Column: created_at
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->label('Fecha'), ->dateTime(), ->sortable()
    Column: event_type
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->label('Evento'), ->badge()
    Column: accessPoint.name
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->label('Punto de acceso')
    Column: accessGrant.pin
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->label('PIN')
    Column: user.email
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->label('Usuario')

  Filters:
    Filter: event_type
      Component: Filament\Tables\Filters\SelectFilter
      Docs: https://filamentphp.com/docs/5.x/tables/filters/select
      Config: ->options(App\Enums\DomoticsEventType::class), ->multiple()
    Filter: access_point_id
      Component: Filament\Tables\Filters\SelectFilter
      Docs: https://filamentphp.com/docs/5.x/tables/filters/select
      Config: ->relationship('accessPoint', 'name'), ->multiple()

---

## 6. Authorization

Policy: App\Policies\PropertyPolicy
  viewAny: user has 'view properties' permission OR owns any property
  view: user is owner of property OR has 'view properties' permission
  create: user has 'create properties' permission
  update: user is owner of property OR has 'edit properties' permission
  delete: user is owner of property OR has 'delete properties' permission

Policy: App\Policies\DevicePolicy
  viewAny: user has 'view devices' permission
  view: user has 'view devices' permission
  create: user has 'create devices' permission
  update: user has 'edit devices' permission OR user has role 'technician' (only status/meta)
  delete: user has 'delete devices' permission

Policy: App\Policies\AccessPointPolicy
  viewAny: user has 'view access points' permission
  view: user has 'view access points' permission
  create: user has 'create access points' permission
  update: user has 'edit access points' permission
  delete: user has 'delete access points' permission
  custom(open): user has 'open access points' permission AND access_point.is_active = true

Policy: App\Policies\AccessGrantPolicy
  viewAny: user has 'view access grants' permission
  view: user owns the grant (user_id matches) OR has 'view access grants' permission
  create: user has 'create access grants' permission
  update: user has 'edit access grants' permission
  delete: user has 'delete access grants' permission

Policy: App\Policies\AutomationPolicy
  viewAny: user has 'view automations' permission
  view: user has 'view automations' permission
  create: user has 'create automations' permission
  update: user has 'edit automations' permission
  delete: user has 'delete automations' permission

Policy: App\Policies\DomoticsEventPolicy
  viewAny: user has 'view events' permission
  view: user has 'view events' permission
  create: deny (events are system-generated)
  update: deny
  delete: user has 'delete events' permission

Roles & Permissions:
  Role: owner
    Permissions: all domotics permissions + manage property members
  Role: admin
    Permissions: view/create/edit/delete devices, access points, access grants, automations, events; open access points
  Role: guest
    Permissions: view own access grants; cannot access panel (PIN is used via public API)
  Role: technician
    Permissions: view devices, edit device status/meta; cannot open access points

Notes:
  - Seed default roles and permissions in `database/seeders/RolesAndPermissionsSeeder`.
  - `guest` users do not access Filament; their PIN is validated by a public API or MCP tool.

---

## 7. Commands / Jobs ( caso de uso portón )

Command: `app:open-access-point {access_point} {user?} {access_grant?}`
  Behavior:
    - Load AccessPoint with its Device
    - Dispatch `OpenAccessPointJob` to queue
    - Job sends open signal through adapter (MQTT/API/Home Assistant) — adapter interface to be implemented
    - Create `DomoticsEvent` type `access_granted` with `access_point_id`, `user_id`, `access_grant_id`
  Authorization:
    - If `access_grant` provided: validate PIN, dates, and access point relation
    - If `user` provided: validate user has 'open access points' permission and belongs to property

Command: `app:validate-access-pin {pin} {access_point}`
  Behavior:
    - Find AccessGrant by PIN within property
    - Check `is_active`, `valid_from` <= now <= `valid_until`
    - Check access point is in grant's `accessPoints`
    - If valid, call `OpenAccessPoint` command
    - If invalid, create `DomoticsEvent` type `access_denied`

Adapter: `App\Services\Domotics\DeviceAdapterInterface`
  Implementations:
    - `HomeAssistantAdapter` (HTTP API or MQTT)
    - `MqttAdapter`
    - `DummyAdapter` (for tests)

---

## 8. Widgets

Widget: DeviceStatsWidget
  Type: StatsOverviewWidget
  Command: php artisan make:filament-widget DeviceStatsWidget --stats-overview --no-interaction
  Location: Dashboard of DomoticsPanel
  Stats:
    Stat: Dispositivos online
      Value: Device::where('status', 'online')->count()
      Color: success
      Icon: Heroicon::Signal
    Stat: Accesos hoy
      Value: DomoticsEvent::whereDate('created_at', today())->where('event_type', 'access_granted')->count()
      Color: primary
      Icon: Heroicon::Key
    Stat: PINs activos
      Value: AccessGrant::where('is_active', true)->where(function ($q) { $q->whereNull('valid_until')->orWhere('valid_until', '>=', now()); })->count()
      Color: warning
      Icon: Heroicon::Ticket
    Stat: Automatizaciones activas
      Value: Automation::where('is_active', true)->count()
      Color: info
      Icon: Heroicon::Bolt

Widget: RecentEventsWidget
  Type: TableWidget
  Command: php artisan make:filament-widget RecentEventsWidget --table --no-interaction
  Location: Dashboard of DomoticsPanel
  Model: DomoticsEvent
  Query: DomoticsEvent::latest()->limit(10)
  Columns:
    Column: created_at
      Component: Filament\Tables\Columns\TextColumn
      Config: ->dateTime()
    Column: event_type
      Component: Filament\Tables\Columns\TextColumn
      Config: ->badge()
    Column: accessPoint.name
      Component: Filament\Tables\Columns\TextColumn

---

## 9. Tests

Authorization:
  - guest user cannot access DomoticsPanel
  - technician cannot see 'open' action on AccessPoint
  - owner can manage all resources in their property
  - user cannot access properties they do not belong to (tenant isolation)

Validation:
  - Property: name required, slug unique per property
  - AccessGrant: valid_until >= valid_from, at least one access point required, PIN unique per property
  - Device: identifier unique per property

Custom Actions:
  - Open action dispatches job and creates DomoticsEvent
  - RegeneratePin creates a new 4-digit PIN
  - ValidateAccessPin: valid PIN opens access point; invalid PIN creates denied event

Multi-tenancy:
  - Resources only show records for current tenant
  - canAccessTenant prevents cross-tenant URL access

---

## 10. Open Questions / Next Phases

- Adaptador físico: ¿MQTT directo, API de Home Assistant, o ambos?
- ¿Las reservas turísticas crean AccessGrants automáticamente? (relación con `Booking`/`NovaExternalBooking`)
- ¿Notificaciones push/SMS cuando se usa un PIN?
- ¿Límite de reintentos o rate limiting para validación de PIN?
- Fase 5+: historial detallado, alertas, geofencing, integración con cámaras.
