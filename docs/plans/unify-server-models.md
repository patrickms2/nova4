# Plan: NovaBusiness como entidad raíz — Unificación de modelos

## Visión

Prioridad actual: **alta**. La duplicidad `Server` / `NovaMcpServer` ya provoca lecturas incorrectas del estado real: el negocio Nova aparece en el panel con 3 MCP servers y 11 tools activas, pero una auditoría basada solo en una capa puede concluir erróneamente que `/mcp/nova` no tiene tools.

Cada cliente (`NovaBusiness`) es la raíz de todo. Desde Filament App, un cliente gestiona todo su ecosistema:

```
NovaBusiness (Taxilanz)
├── Servicios (NovaService)
│   └── WhatsApp, IA Profiles
├── MCP Servers  ← actualmente divididos en dos modelos
│   ├── Tools
│   ├── Resources
│   └── Prompts
├── Conocimiento IA (NovaAiKnowledge)
├── Chats / Conversaciones
├── Reglas / Agentes
└── Reservas / Taxi Bookings
```

Hoy los `Server` del hub (Sirvo, La Geria, Taxilanz Hoteles) no están vinculados a ningún `NovaBusiness`, aunque representan a esos clientes. El objetivo es que **todo server esté vinculado a un cliente**, y que desde el panel del cliente se gestione todo.

---

## Fase 1: Unificar Server + NovaMcpServer

### Contexto actual

Actualmente existen dos modelos que representan servidores MCP con roles distintos pero solapados:

| Modelo | Tabla | Registros | Rol |
|---|---|---|---|
| `Server` | `servers` | 6 | Servers que el hub **expone** (tiene Tools, Resources, Prompts) |
| `NovaMcpServer` | `nova_mcp_servers` | 4 | Servers externos que Nova **consume** (cliente, ligado a NovaBusiness) |

## Diferencias de esquema

**`servers`** tiene: `name`, `slug`, `description`, `version`, `instructions`, `transport`, `endpoint`, `middleware`, `metadata`, `is_active`

**`nova_mcp_servers`** tiene: `nova_business_id`, `nova_service_id`, `name`, `type`, `endpoint_url`, `auth_type`, `status`, `capabilities`, `credentials`, `last_checked_at`, `last_error`

## Objetivo

Un único modelo `Server` que cubra ambos roles:
- Servers **locales** (expuestos por el hub) → `nova_business_id IS NULL`
- Servers **externos** (consumidos por Nova) → `nova_business_id IS NOT NULL`

`NovaMcpServer` se convierte en un alias con scope automático para no romper referencias existentes.

---

## Pasos de implementación

### Paso 1 — Migración

```bash
php artisan make:migration extend_servers_for_nova_mcp --table=servers
```

Añadir columnas a `servers`:

```php
$table->foreignId('nova_business_id')->nullable()->constrained('nova_businesses')->nullOnDelete();
$table->foreignId('nova_service_id')->nullable()->constrained('nova_services')->nullOnDelete();
$table->string('type')->nullable();               // 'local' | 'remote'
$table->string('auth_type')->nullable();          // 'bearer' | 'basic' | null
$table->json('credentials')->nullable();
$table->string('status')->default('active');      // 'active' | 'error' | 'draft'
$table->json('capabilities')->nullable();
$table->timestamp('last_checked_at')->nullable();
$table->text('last_error')->nullable();
```

Migrar datos de `nova_mcp_servers` → `servers` en el mismo up():

```php
DB::table('nova_mcp_servers')->get()->each(function ($row) {
    DB::table('servers')->insert([
        'nova_business_id' => $row->nova_business_id,
        'nova_service_id'  => $row->nova_service_id,
        'name'             => $row->name,
        'slug'             => Str::slug($row->name . '-' . $row->id),
        'type'             => $row->type ?? 'remote',
        'endpoint'         => $row->endpoint_url,
        'auth_type'        => $row->auth_type,
        'credentials'      => $row->credentials,
        'status'           => $row->status,
        'capabilities'     => $row->capabilities,
        'last_checked_at'  => $row->last_checked_at,
        'last_error'       => $row->last_error,
        'is_active'        => $row->status === 'active',
        'created_at'       => $row->created_at,
        'updated_at'       => $row->updated_at,
    ]);
});
```

### Paso 2 — Actualizar `Server` model

Añadir relaciones y scopes:

```php
// Relaciones nuevas
public function business(): BelongsTo
{
    return $this->belongsTo(NovaBusiness::class, 'nova_business_id');
}

public function service(): BelongsTo
{
    return $this->belongsTo(NovaService::class, 'nova_service_id');
}

// Scopes
public function scopeLocal(Builder $query): Builder
{
    return $query->whereNull('nova_business_id');
}

public function scopeForNova(Builder $query): Builder
{
    return $query->whereNotNull('nova_business_id');
}

public function scopeActive(Builder $query): Builder
{
    return $query->where('is_active', true);
}
```

### Paso 3 — Convertir `NovaMcpServer` en alias

Reemplazar el contenido de `NovaMcpServer.php` para que extienda `Server` con scope automático, sin romper ninguna referencia existente:

```php
class NovaMcpServer extends Server
{
    protected $table = 'servers';

    protected static function booted(): void
    {
        static::addGlobalScope('nova', fn (Builder $q) => $q->whereNotNull('nova_business_id'));
    }

    // Mantener fillable de nova_mcp_servers para compatibilidad
    protected $fillable = [
        'nova_business_id', 'nova_service_id', 'name', 'type',
        'endpoint', 'auth_type', 'status', 'capabilities',
        'credentials', 'last_checked_at', 'last_error',
    ];
}
```

### Paso 4 — Actualizar referencias de `endpoint_url` → `endpoint`

Buscar y reemplazar en todos los archivos:

```bash
grep -r "endpoint_url" app/ --include="*.php" -l
```

Archivos afectados conocidos:
- `app/Services/Nova/NovaMcpClient.php`
- `app/Services/Nova/SirvoReservationClient.php`
- `app/Services/Nova/NovaOrchestratorService.php`
- `app/Filament/App/Resources/NovaMcpServers/`
- `app/Console/Commands/NovaSeedLocalMcpHub.php`
- `app/Console/Commands/NovaIntegrationCheck.php`

### Paso 5 — Eliminar tabla `nova_mcp_servers`

Una vez verificado que todo funciona:

```php
Schema::dropIfExists('nova_mcp_servers');
```

---

## Archivos que referencian `NovaMcpServer` (~18 archivos)

```
app/Models/NovaMcpServer.php                              ← reemplazar contenido
app/Models/NovaBusiness.php                               ← relación mcpServers()
app/Livewire/ServerChat.php
app/Filament/App/Resources/NovaMcpServers/               ← actualizar tabla endpoint_url → endpoint
app/Filament/App/Resources/NovaBusinesses/RelationManagers/McpServersRelationManager.php
app/Services/Nova/NovaOrchestratorService.php
app/Services/Nova/NovaMcpCreationService.php
app/Services/Nova/NovaMcpClient.php
app/Services/Nova/SirvoReservationClient.php
app/Console/Commands/NovaIntegrationCheck.php
app/Console/Commands/NovaSeedLocalMcpHub.php
app/Providers/Filament/AdminPanelProvider.php
```

## Tests a escribir antes

- `Server::scopeLocal()` devuelve solo servers sin `nova_business_id`
- `Server::scopeForNova()` devuelve solo servers con `nova_business_id`
- `NovaMcpServer::all()` solo devuelve registros con `nova_business_id` (global scope)
- Migración de datos: los 4 registros de `nova_mcp_servers` aparecen en `servers`

## Riesgo

- **Medio** — muchos archivos pero cambios mecánicos (`endpoint_url` → `endpoint`)
- El alias `NovaMcpServer extends Server` garantiza que no hay breaking changes en ningún código que lo use
- La tabla `nova_mcp_servers` se puede mantener hasta verificar estabilidad

## Estado real — Fase 1

- [x] Paso 1: Migración parcial — `servers` ya tiene `nova_business_id` y campos operativos básicos (`type`, `auth_type`, `credentials`, `status`, `last_checked_at`, `last_error`).
- [~] Paso 2: Server model parcial — `Server` tiene `nova_business_id` y relación con `NovaBusiness`, pero faltan `nova_service_id`, `auth_type`, `credentials`, `status`, `capabilities`, scopes `local/forNova/active` y fillable/casts completos.
- [ ] Paso 3: NovaMcpServer alias — no realizado. `NovaMcpServer` sigue siendo modelo independiente sobre `nova_mcp_servers` y usa `endpoint_url`.
- [ ] Paso 4: endpoint_url → endpoint — no realizado de forma completa; siguen referencias legacy en `NovaMcpServer` y servicios que consultan `nova_mcp_servers`.
- [ ] Paso 5: Drop nova_mcp_servers — no realizado; debe mantenerse hasta migrar datos y referencias.
- [~] Tests — hay tests focalizados de IA/Knowledge, pero faltan tests específicos de unificación `Server`/`NovaMcpServer`.

Conclusión: la unificación está **parcialmente iniciada**, pero `Server` todavía no es la única fuente de verdad MCP. El siguiente paso seguro es completar el alias `NovaMcpServer extends Server` o migrar referencias de forma controlada antes de eliminar `nova_mcp_servers`.

Criterio de aceptación inmediato: cualquier auditoría, agente o chatbot debe ver para el negocio Nova los mismos datos que Filament muestra actualmente: 3 MCP servers asociados y 11 tools activas, incluyendo `/mcp/nova` con 9 tools activas.

---

## Fase 2: NovaBusiness como raíz de todo

### Objetivo

Vincular los 6 servers del hub admin a su `NovaBusiness` correspondiente y que el panel del cliente muestre y gestione todo.

### Mapeo inicial servers → businesses

| Server (admin hub) | NovaBusiness a vincular |
|---|---|
| Sirvo Restaurants MCP | Sirvo (o el negocio restauración) |
| La Geria Shop+Tours MCP | La Geria |
| Taxilanz Hoteles Laravel MCP | Taxilanz |
| Taxilanz Chauffeur Booking MCP | Taxilanz |
| Lanzaloe Magento MCP | Lanzaloe |
| Nova Hub MCP | — (hub propio, sin business) |

### Cambios en el panel App (Filament)

Desde `NovaBusiness → MCP Servers`, el cliente ve y gestiona **sus** servers unificados:

```
Taxilanz
└── MCP Servers
    ├── Taxilanz Hoteles Laravel MCP  (ex-Server del hub)
    │   ├── Tools: hotel_list, servicios_list...
    │   ├── Resources
    │   └── Prompts: use-server (listing_tool, listing_intro, listing_cta)
    └── MAntenimiento  (ex-NovaMcpServer)
        └── Tools: remote-http-check...
```

### Beneficios directos

- `promptMetaFor()` en `NovaOrchestratorService` puede buscar por `business` en lugar de por slug keyword
- El knowledge base, los agentes y los tools de un cliente están todos en un único sitio
- Nuevo cliente = crear `NovaBusiness` + asignar sus servers → todo funciona
- Admin panel del hub queda como vista global/técnica; App panel como vista del cliente

### Pasos Fase 2

1. **Seed de vinculaciones**: script que asigna `nova_business_id` a los 6 servers existentes
2. **Relación `NovaBusiness → servers()`**: añadir `hasMany(Server::class)` al model
3. **Actualizar panel App** (`NovaMcpServers`): mostrar servers de `Server` model filtrados por business (además de los `NovaMcpServer` ya existentes)
4. **Actualizar `promptMetaFor()`**: usar `business->servers()->...` en lugar de slug keyword
5. **Actualizar `NovaKnowledgeService`**: opcionalmente leer también del `content` de Resources vinculados al business
6. **RelationManager unificado** en `NovaBusinessResource` que muestre tools/resources/prompts del server

### Estado — Fase 2

- [ ] Seed vinculaciones servers → businesses
- [ ] `NovaBusiness::servers()` hasMany
- [ ] Panel App muestra servers unificados
- [ ] `promptMetaFor()` usa business
- [ ] Tests
