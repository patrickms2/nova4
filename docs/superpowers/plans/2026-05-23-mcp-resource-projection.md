# MCP Resource Projection Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Project synchronized MCP records into native local models so they appear in `/explore` and the matching Filament resource with visible source attribution.

**Architecture:** Keep `external_*` tables as staging/audit storage. Add explicit resource classification on `ExternalSource`, add `external_sync_mappings` as the polymorphic link from remote records to local models, then route normalized records through small projector classes. Existing Filament resources and `/explore` continue to query native models and resolve source metadata through mappings.

**Tech Stack:** Laravel 12, Eloquent, Filament, PHPUnit feature tests, existing `App\Services\ExternalSync` services.

---

## File Structure

- Create `database/migrations/2026_05_23_000002_add_projection_fields_to_external_sources.php`: add source classification columns.
- Create `database/migrations/2026_05_23_000003_create_external_sync_mappings_table.php`: local projection mapping table.
- Create `app/Models/ExternalSyncMapping.php`: Eloquent model for mappings.
- Modify `app/Models/ExternalSource.php`: fillable fields and mapping relation.
- Modify `app/Models/Hotel.php`, `Restaurant.php`, `Tour.php`, `TaxiService.php`: `externalSyncMappings()` morph-like relation helper using `target_model` and `target_id`.
- Modify `app/Services/ExternalSync/ExternalSourceRegistrar.php`: register explicit sync targets from server metadata.
- Modify `database/seeders/LocalMcpServersSeeder.php`: add `sync_targets` metadata to existing MCP server definitions.
- Create `app/Services/ExternalSync/Projection/ExternalProjectionPayload.php`: stable payload DTO.
- Create `app/Services/ExternalSync/Projection/Projector.php`: projector contract.
- Create `app/Services/ExternalSync/Projection/ExternalProjectionManager.php`: routes payloads to projectors and writes mappings.
- Create `app/Services/ExternalSync/Projection/Concerns/ResolvesProjectionLocation.php`: shared location resolver.
- Create `app/Services/ExternalSync/Projection/HotelProjector.php`, `RestaurantProjector.php`, `TourProjector.php`, `TaxiServiceProjector.php`, `ProductProjector.php`: first projectors.
- Modify `app/Services/ExternalSync/ExternalSyncManager.php`: call projection manager after staging upserts.
- Modify `app/Services/ExternalSync/ExternalSourceSynchronizer.php`: set `resource_type` in normalized payloads where source metadata does not already define it.
- Modify `app/Http/Controllers/PublicExploreController.php`: include source metadata in place payloads.
- Modify Filament resources: `app/Filament/HotelAdmin/Resources/HotelResource.php`, `app/Filament/RestaurantAdmin/Resources/RestaurantResource.php`, `app/Filament/TourAdmin/Resources/TourResource.php`, `app/Filament/Resources/TaxiServiceResource.php`, existing external resources.
- Create tests:
  - `tests/Feature/ExternalProjectionSchemaTest.php`
  - `tests/Feature/ExternalSourceRegistrarSyncTargetsTest.php`
  - `tests/Feature/ExternalProjectionManagerTest.php`
  - `tests/Feature/ExploreProjectedSourcesTest.php`
  - `tests/Feature/ProjectedFilamentResourcesTest.php`

No commit steps are included because this workspace is not a Git repository. If this plan is executed from a Git repo, commit after each task with the task title as the commit message.

---

### Task 1: Projection Schema and Model

**Files:**
- Create: `database/migrations/2026_05_23_000002_add_projection_fields_to_external_sources.php`
- Create: `database/migrations/2026_05_23_000003_create_external_sync_mappings_table.php`
- Create: `app/Models/ExternalSyncMapping.php`
- Modify: `app/Models/ExternalSource.php`
- Test: `tests/Feature/ExternalProjectionSchemaTest.php`

- [ ] **Step 1: Write the failing schema test**

Create `tests/Feature/ExternalProjectionSchemaTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\ExternalSource;
use App\Models\ExternalSyncMapping;
use App\Models\Hotel;
use App\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalProjectionSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_external_sources_store_projection_classification(): void
    {
        $server = Server::query()->create(['name' => 'Taxilanz Hoteles', 'slug' => 'taxilanz-hoteles']);

        $source = ExternalSource::query()->create([
            'server_id' => $server->id,
            'name' => 'Taxilanz Hoteles MCP',
            'business_name' => 'Taxilanz Hoteles',
            'source_platform' => 'mcp',
            'source_label' => 'Taxilanz Hoteles · MCP · Hoteles',
            'connection_type' => 'api',
            'resource_type' => 'hotel',
            'target_model' => 'hotel',
            'sync_direction' => 'remote_to_local',
            'capability' => 'hotels',
            'status' => 'active',
        ]);

        $this->assertSame('hotel', $source->resource_type);
        $this->assertSame('hotel', $source->target_model);
        $this->assertSame('remote_to_local', $source->sync_direction);
        $this->assertSame('hotels', $source->capability);
    }

    public function test_external_sync_mapping_links_remote_record_to_local_model(): void
    {
        $server = Server::query()->create(['name' => 'Taxilanz Hoteles', 'slug' => 'taxilanz-hoteles']);
        $source = ExternalSource::query()->create([
            'server_id' => $server->id,
            'name' => 'Taxilanz Hoteles MCP',
            'business_name' => 'Taxilanz Hoteles',
            'source_platform' => 'mcp',
            'source_label' => 'Taxilanz Hoteles · MCP · Hoteles',
            'connection_type' => 'api',
            'resource_type' => 'hotel',
            'target_model' => 'hotel',
            'status' => 'active',
        ]);
        $hotel = Hotel::query()->create(['name' => 'Hotel Volcan', 'is_active' => true]);

        $mapping = ExternalSyncMapping::query()->create([
            'server_id' => $server->id,
            'external_source_id' => $source->id,
            'business_name' => 'Taxilanz Hoteles',
            'source_platform' => 'mcp',
            'source_label' => 'Taxilanz Hoteles · MCP · Hoteles',
            'resource_type' => 'hotel',
            'target_model' => 'hotel',
            'target_id' => $hotel->id,
            'external_id' => 'hotel-123',
            'payload_hash' => sha1('hotel-123'),
            'last_synced_at' => now(),
        ]);

        $this->assertTrue($mapping->source->is($source));
        $this->assertSame('Taxilanz Hoteles · MCP · Hoteles', $hotel->externalSyncMappings()->first()->source_label);
    }
}
```

- [ ] **Step 2: Run the schema test to verify it fails**

Run: `php artisan test tests/Feature/ExternalProjectionSchemaTest.php`

Expected: FAIL because `resource_type` columns and `ExternalSyncMapping` do not exist.

- [ ] **Step 3: Add projection fields migration**

Create `database/migrations/2026_05_23_000002_add_projection_fields_to_external_sources.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_sources', function (Blueprint $table): void {
            $table->string('resource_type')->nullable()->after('source_label');
            $table->string('target_model')->nullable()->after('resource_type');
            $table->string('sync_direction')->default('remote_to_local')->after('target_model');
            $table->string('capability')->nullable()->after('sync_direction');
            $table->index(['resource_type', 'target_model']);
        });
    }

    public function down(): void
    {
        Schema::table('external_sources', function (Blueprint $table): void {
            $table->dropIndex(['resource_type', 'target_model']);
            $table->dropColumn(['resource_type', 'target_model', 'sync_direction', 'capability']);
        });
    }
};
```

- [ ] **Step 4: Add mappings migration**

Create `database/migrations/2026_05_23_000003_create_external_sync_mappings_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_sync_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
            $table->foreignId('external_source_id')->constrained('external_sources')->cascadeOnDelete();
            $table->string('business_name')->nullable();
            $table->string('source_platform');
            $table->string('source_label');
            $table->string('resource_type');
            $table->string('target_model');
            $table->unsignedBigInteger('target_id');
            $table->string('external_id');
            $table->string('external_item_id')->nullable();
            $table->string('payload_hash')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['external_source_id', 'resource_type', 'external_id', 'external_item_id'],
                'external_sync_mapping_remote_unique'
            );
            $table->index(['target_model', 'target_id']);
            $table->index(['resource_type', 'source_platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_sync_mappings');
    }
};
```

- [ ] **Step 5: Add mapping model**

Create `app/Models/ExternalSyncMapping.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalSyncMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'external_source_id',
        'business_name',
        'source_platform',
        'source_label',
        'resource_type',
        'target_model',
        'target_id',
        'external_id',
        'external_item_id',
        'payload_hash',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(ExternalSource::class, 'external_source_id');
    }
}
```

- [ ] **Step 6: Update `ExternalSource`**

Modify `app/Models/ExternalSource.php`:

```php
// Add to $fillable:
'resource_type',
'target_model',
'sync_direction',
'capability',

// Add method:
public function syncMappings(): HasMany
{
    return $this->hasMany(ExternalSyncMapping::class);
}
```

- [ ] **Step 7: Add mapping relation helpers to native models**

Add this method to `Hotel`, `Restaurant`, `Tour`, and `TaxiService`, changing the alias for each model:

```php
use Illuminate\Database\Eloquent\Relations\HasMany;

public function externalSyncMappings(): HasMany
{
    return $this->hasMany(ExternalSyncMapping::class, 'target_id', 'id')
        ->where('target_model', 'hotel');
}
```

Use these aliases:

- `Hotel`: `hotel`
- `Restaurant`: `restaurant`
- `Tour`: `tour`
- `TaxiService`: `taxi_service`

Add `use App\Models\ExternalSyncMapping;` where needed.

- [ ] **Step 8: Run the schema test**

Run: `php artisan test tests/Feature/ExternalProjectionSchemaTest.php`

Expected: PASS.

---

### Task 2: Register Explicit MCP Sync Targets

**Files:**
- Modify: `app/Services/ExternalSync/ExternalSourceRegistrar.php`
- Modify: `database/seeders/LocalMcpServersSeeder.php`
- Test: `tests/Feature/ExternalSourceRegistrarSyncTargetsTest.php`

- [ ] **Step 1: Write the failing registrar test**

Create `tests/Feature/ExternalSourceRegistrarSyncTargetsTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\ExternalSource;
use App\Models\Server;
use App\Services\ExternalSync\ExternalSourceRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalSourceRegistrarSyncTargetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registers_one_source_per_explicit_sync_target(): void
    {
        $server = Server::query()->create([
            'name' => 'Taxilanz Rutas Woo MCP',
            'slug' => 'taxilanz-rutas-woo',
            'metadata' => [
                'business' => 'Taxilanz',
                'remote_endpoint' => 'https://taxilanzwp.test',
                'source_stack' => ['wordpress', 'woocommerce', 'routes', 'mcp'],
                'sync_targets' => [
                    [
                        'capability' => 'woocommerce_products',
                        'source_platform' => 'woo',
                        'resource_type' => 'tour_route',
                        'target_model' => 'tour',
                        'source_label_suffix' => 'Rutas',
                    ],
                    [
                        'capability' => 'woocommerce_orders',
                        'source_platform' => 'woo',
                        'resource_type' => 'tour_booking',
                        'target_model' => 'tour_booking',
                        'source_label_suffix' => 'Reservas rutas',
                    ],
                ],
            ],
        ]);

        $sources = app(ExternalSourceRegistrar::class)->registerForServer($server);

        $this->assertCount(2, $sources);
        $this->assertDatabaseHas('external_sources', [
            'server_id' => $server->id,
            'business_name' => 'Taxilanz',
            'source_platform' => 'woo',
            'source_label' => 'Taxilanz · Woo · Rutas',
            'resource_type' => 'tour_route',
            'target_model' => 'tour',
            'sync_direction' => 'remote_to_local',
            'capability' => 'woocommerce_products',
        ]);
        $this->assertDatabaseHas('external_sources', [
            'server_id' => $server->id,
            'source_label' => 'Taxilanz · Woo · Reservas rutas',
            'resource_type' => 'tour_booking',
            'target_model' => 'tour_booking',
            'capability' => 'woocommerce_orders',
        ]);
    }

    public function test_fallback_source_stack_registration_still_works(): void
    {
        $server = Server::query()->create([
            'name' => 'Lanzaloe Magento MCP',
            'slug' => 'lanzaloe-magento',
            'metadata' => [
                'business' => 'Lanzaloe',
                'source_stack' => ['magento', 'mcp'],
            ],
        ]);

        app(ExternalSourceRegistrar::class)->registerForServer($server);

        $source = ExternalSource::query()->sole();
        $this->assertSame('Lanzaloe · Magento', $source->source_label);
        $this->assertSame('generic_product', $source->resource_type);
        $this->assertSame('external_catalog_item', $source->target_model);
    }
}
```

- [ ] **Step 2: Run the registrar test to verify it fails**

Run: `php artisan test tests/Feature/ExternalSourceRegistrarSyncTargetsTest.php`

Expected: FAIL because explicit `sync_targets` are ignored and fallback sources do not set resource classification.

- [ ] **Step 3: Update registrar definitions**

Modify `ExternalSourceRegistrar::definitionsFor()` so it starts with:

```php
$syncTargets = $metadata['sync_targets'] ?? [];

if (is_array($syncTargets) && $syncTargets !== []) {
    return collect($syncTargets)
        ->map(fn (array $target): array => $this->definitionFromSyncTarget($server, $business, $baseUrl, $target))
        ->all();
}
```

Add this method:

```php
private function definitionFromSyncTarget(Server $server, string $business, ?string $baseUrl, array $target): array
{
    $platform = Str::lower((string) ($target['source_platform'] ?? 'mcp'));
    $platformLabel = match ($platform) {
        'woo' => 'Woo',
        'latepoint' => 'LatePoint',
        'magento' => 'Magento',
        'sirvo' => 'Reservas',
        default => Str::headline($platform),
    };
    $suffix = trim((string) ($target['source_label_suffix'] ?? ''));
    $sourceLabel = trim("{$business} · {$platformLabel}".($suffix !== '' ? " · {$suffix}" : ''));

    return $this->definition(
        $server,
        $business,
        $platform,
        $platformLabel,
        (string) ($target['connection_type'] ?? 'api'),
        $baseUrl,
        [
            'source_label' => $sourceLabel,
            'resource_type' => $target['resource_type'] ?? null,
            'target_model' => $target['target_model'] ?? null,
            'sync_direction' => $target['sync_direction'] ?? 'remote_to_local',
            'capability' => $target['capability'] ?? null,
            'settings' => ['sync_target' => $target],
        ],
    );
}
```

Change `definition()` signature to accept overrides:

```php
private function definition(
    Server $server,
    string $business,
    string $platform,
    string $platformLabel,
    string $connectionType,
    ?string $baseUrl,
    array $overrides = [],
): array {
    $definition = [
        'server_id' => $server->id,
        'name' => "{$business} {$platformLabel}",
        'business_name' => $business,
        'source_platform' => $platform,
        'source_label' => "{$business} · {$platformLabel}",
        'connection_type' => $connectionType,
        'base_url' => $baseUrl,
        'api_url' => $baseUrl,
        'resource_type' => $this->defaultResourceType($platform),
        'target_model' => $this->defaultTargetModel($platform),
        'sync_direction' => 'remote_to_local',
        'status' => 'active',
        'settings' => [
            'registered_from' => 'server_metadata',
            'server_slug' => $server->slug,
        ],
    ];

    if (isset($overrides['settings']) && is_array($overrides['settings'])) {
        $overrides['settings'] = array_merge($definition['settings'], $overrides['settings']);
    }

    return array_merge($definition, $overrides);
}
```

Add defaults:

```php
private function defaultResourceType(string $platform): ?string
{
    return match ($platform) {
        'woo', 'magento' => 'generic_product',
        'latepoint', 'sirvo' => 'restaurant_booking',
        default => null,
    };
}

private function defaultTargetModel(string $platform): ?string
{
    return match ($platform) {
        'woo', 'magento' => 'external_catalog_item',
        'latepoint', 'sirvo' => 'restaurant_booking',
        default => null,
    };
}
```

- [ ] **Step 4: Add explicit sync targets to seeded servers**

In `database/seeders/LocalMcpServersSeeder.php`, add `sync_targets` arrays to server definitions:

```php
'sync_targets' => [
    [
        'capability' => 'latepoint_bookings',
        'source_platform' => 'latepoint',
        'resource_type' => 'restaurant_booking',
        'target_model' => 'restaurant_booking',
        'source_label_suffix' => 'Reservas restaurante',
    ],
    [
        'capability' => 'woocommerce_orders',
        'source_platform' => 'woo',
        'resource_type' => 'wine_product',
        'target_model' => 'external_catalog_item',
        'source_label_suffix' => 'Vinos',
    ],
]
```

Use these minimum mappings:

- `sirvo-restaurantes`: `restaurant` and `restaurant_booking`.
- `la-geria-wordpress-woo-latepoint`: `wine_product` and `restaurant_booking`.
- `taxilanz-rutas-woo`: `tour_route` and `tour_booking`.
- `taxilanz-chauffeur-booking`: `taxi` and `taxi_booking`.
- `taxilanz-hoteles-laravel`: `hotel`.
- `lanzaloe-magento`: `aloe_product`.

- [ ] **Step 5: Run registrar tests**

Run: `php artisan test tests/Feature/RegisterExternalSourcesTest.php tests/Feature/ExternalSourceRegistrarSyncTargetsTest.php`

Expected: PASS.

---

### Task 3: Projection Contracts and Manager

**Files:**
- Create: `app/Services/ExternalSync/Projection/ExternalProjectionPayload.php`
- Create: `app/Services/ExternalSync/Projection/Projector.php`
- Create: `app/Services/ExternalSync/Projection/ExternalProjectionManager.php`
- Create: `app/Services/ExternalSync/Projection/ProductProjector.php`
- Test: `tests/Feature/ExternalProjectionManagerTest.php`

- [ ] **Step 1: Write failing manager tests**

Create `tests/Feature/ExternalProjectionManagerTest.php` with the first test:

```php
<?php

namespace Tests\Feature;

use App\Models\ExternalCatalogItem;
use App\Models\ExternalSource;
use App\Models\ExternalSyncMapping;
use App\Models\Server;
use App\Services\ExternalSync\Projection\ExternalProjectionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalProjectionManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_projection_records_mapping_to_external_catalog_item(): void
    {
        $source = $this->source('wine_product', 'external_catalog_item');
        $catalogItem = ExternalCatalogItem::query()->create([
            'server_id' => $source->server_id,
            'external_source_id' => $source->id,
            'business_name' => $source->business_name,
            'source_platform' => $source->source_platform,
            'source_label' => $source->source_label,
            'external_id' => 'sku-1',
            'type' => 'product',
            'name' => 'Vino Malvasia',
            'metadata' => ['raw' => ['id' => 'sku-1']],
        ]);

        $result = app(ExternalProjectionManager::class)->project($source, $catalogItem, [
            'external_id' => 'sku-1',
            'name' => 'Vino Malvasia',
            'metadata' => ['raw' => ['id' => 'sku-1']],
        ]);

        $this->assertSame('external_catalog_item', $result->target_model);
        $this->assertSame($catalogItem->id, $result->target_id);
        $this->assertDatabaseHas('external_sync_mappings', [
            'external_source_id' => $source->id,
            'resource_type' => 'wine_product',
            'target_model' => 'external_catalog_item',
            'target_id' => $catalogItem->id,
            'external_id' => 'sku-1',
        ]);
    }

    private function source(string $resourceType, string $targetModel): ExternalSource
    {
        $server = Server::query()->create(['name' => 'La Geria', 'slug' => 'la-geria']);

        return ExternalSource::query()->create([
            'server_id' => $server->id,
            'name' => 'La Geria Woo Vinos',
            'business_name' => 'La Geria',
            'source_platform' => 'woo',
            'source_label' => 'La Geria · Woo · Vinos',
            'connection_type' => 'api',
            'resource_type' => $resourceType,
            'target_model' => $targetModel,
            'status' => 'active',
        ]);
    }
}
```

- [ ] **Step 2: Run manager test to verify it fails**

Run: `php artisan test tests/Feature/ExternalProjectionManagerTest.php`

Expected: FAIL because projection classes do not exist.

- [ ] **Step 3: Add payload DTO**

Create `app/Services/ExternalSync/Projection/ExternalProjectionPayload.php`:

```php
<?php

namespace App\Services\ExternalSync\Projection;

use App\Models\ExternalSource;
use Illuminate\Database\Eloquent\Model;

class ExternalProjectionPayload
{
    public function __construct(
        public readonly ExternalSource $source,
        public readonly Model $stagingRecord,
        public readonly array $payload,
    ) {}

    public function externalId(): string
    {
        return (string) ($this->payload['external_id'] ?? $this->stagingRecord->external_id);
    }

    public function externalItemId(): ?string
    {
        $value = $this->payload['external_item_id'] ?? $this->stagingRecord->external_item_id ?? null;

        return blank($value) ? null : (string) $value;
    }

    public function resourceType(): string
    {
        return (string) ($this->payload['resource_type'] ?? $this->source->resource_type);
    }

    public function targetModel(): string
    {
        return (string) ($this->payload['target_model'] ?? $this->source->target_model);
    }

    public function raw(): array
    {
        return $this->payload['metadata']['raw'] ?? $this->stagingRecord->metadata['raw'] ?? $this->payload;
    }
}
```

- [ ] **Step 4: Add projector contract**

Create `app/Services/ExternalSync/Projection/Projector.php`:

```php
<?php

namespace App\Services\ExternalSync\Projection;

use Illuminate\Database\Eloquent\Model;

interface Projector
{
    public function project(ExternalProjectionPayload $payload): Model;
}
```

- [ ] **Step 5: Add product projector**

Create `app/Services/ExternalSync/Projection/ProductProjector.php`:

```php
<?php

namespace App\Services\ExternalSync\Projection;

use Illuminate\Database\Eloquent\Model;

class ProductProjector implements Projector
{
    public function project(ExternalProjectionPayload $payload): Model
    {
        return $payload->stagingRecord;
    }
}
```

- [ ] **Step 6: Add projection manager**

Create `app/Services/ExternalSync/Projection/ExternalProjectionManager.php`:

```php
<?php

namespace App\Services\ExternalSync\Projection;

use App\Models\ExternalSource;
use App\Models\ExternalSyncMapping;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ExternalProjectionManager
{
    public function __construct(
        private readonly ProductProjector $productProjector,
    ) {}

    public function project(ExternalSource $source, Model $stagingRecord, array $payload): ExternalSyncMapping
    {
        $projectionPayload = new ExternalProjectionPayload($source, $stagingRecord, $payload);
        $projected = $this->projectorFor($projectionPayload)->project($projectionPayload);

        return ExternalSyncMapping::query()->updateOrCreate(
            [
                'external_source_id' => $source->id,
                'resource_type' => $projectionPayload->resourceType(),
                'external_id' => $projectionPayload->externalId(),
                'external_item_id' => $projectionPayload->externalItemId(),
            ],
            [
                'server_id' => $source->server_id,
                'business_name' => $source->business_name,
                'source_platform' => $source->source_platform,
                'source_label' => $source->source_label,
                'target_model' => $projectionPayload->targetModel(),
                'target_id' => $projected->getKey(),
                'payload_hash' => sha1(json_encode($projectionPayload->raw())),
                'last_synced_at' => now(),
            ],
        );
    }

    private function projectorFor(ExternalProjectionPayload $payload): Projector
    {
        return match (true) {
            Str::contains($payload->resourceType(), 'product') => $this->productProjector,
            default => $this->productProjector,
        };
    }
}
```

- [ ] **Step 7: Run manager test**

Run: `php artisan test tests/Feature/ExternalProjectionManagerTest.php`

Expected: PASS.

---

### Task 4: Native Place Projectors

**Files:**
- Create: `app/Services/ExternalSync/Projection/Concerns/ResolvesProjectionLocation.php`
- Create: `app/Services/ExternalSync/Projection/HotelProjector.php`
- Create: `app/Services/ExternalSync/Projection/RestaurantProjector.php`
- Create: `app/Services/ExternalSync/Projection/TourProjector.php`
- Create: `app/Services/ExternalSync/Projection/TaxiServiceProjector.php`
- Modify: `app/Services/ExternalSync/Projection/ExternalProjectionManager.php`
- Test: `tests/Feature/ExternalProjectionManagerTest.php`

- [ ] **Step 1: Add failing projection tests**

Append these tests to `ExternalProjectionManagerTest`:

```php
public function test_projects_hotel_payload_to_hotel_model_idempotently(): void
{
    $source = $this->source('hotel', 'hotel');

    $payload = [
        'external_id' => 'hotel-1',
        'name' => 'Hotel Mirador',
        'description' => 'Sea view hotel',
        'phone' => '+34 928 000 000',
        'email' => 'hotel@example.test',
        'website' => 'https://hotel.example.test',
        'metadata' => [
            'raw' => [
                'address' => 'Arrecife',
                'city' => 'Arrecife',
                'country' => 'Spain',
                'latitude' => 28.963,
                'longitude' => -13.547,
            ],
        ],
    ];

    $first = app(ExternalProjectionManager::class)->project($source, new ExternalCatalogItem($payload), $payload);
    $second = app(ExternalProjectionManager::class)->project($source, new ExternalCatalogItem($payload + ['name' => 'Hotel Mirador Updated']), $payload + ['name' => 'Hotel Mirador Updated']);

    $this->assertSame($first->target_id, $second->target_id);
    $this->assertDatabaseHas('hotels', ['id' => $first->target_id, 'name' => 'Hotel Mirador Updated']);
    $this->assertSame(1, \App\Models\Hotel::query()->count());
}

public function test_projects_restaurant_tour_and_taxi_payloads(): void
{
    $manager = app(ExternalProjectionManager::class);

    $restaurantSource = $this->source('restaurant', 'restaurant');
    $tourSource = $this->source('tour_route', 'tour');
    $taxiSource = $this->source('taxi', 'taxi_service');

    $manager->project($restaurantSource, new ExternalCatalogItem(), [
        'external_id' => 'restaurant-1',
        'name' => 'Bodega Restaurant',
        'description' => 'Local food',
        'metadata' => ['raw' => ['city' => 'Yaiza', 'country' => 'Spain', 'latitude' => 28.956, 'longitude' => -13.765]],
    ]);
    $manager->project($tourSource, new ExternalCatalogItem(), [
        'external_id' => 'tour-1',
        'name' => 'Ruta Volcanes',
        'description' => 'Volcano route',
        'price' => 49.50,
        'metadata' => ['raw' => ['city' => 'Tinajo', 'country' => 'Spain', 'latitude' => 29.063, 'longitude' => -13.676]],
    ]);
    $manager->project($taxiSource, new ExternalCatalogItem(), [
        'external_id' => 'taxi-1',
        'name' => 'Taxilanz Transfers',
        'description' => 'Airport transfers',
        'metadata' => ['raw' => ['city' => 'Tias', 'country' => 'Spain', 'latitude' => 28.953, 'longitude' => -13.608]],
    ]);

    $this->assertDatabaseHas('restaurants', ['name' => 'Bodega Restaurant']);
    $this->assertDatabaseHas('tours', ['name' => 'Ruta Volcanes']);
    $this->assertDatabaseHas('taxi_services', ['name' => 'Taxilanz Transfers']);
}
```

Add these imports:

```php
use App\Models\ExternalCatalogItem;
```

- [ ] **Step 2: Run projection tests to verify they fail**

Run: `php artisan test tests/Feature/ExternalProjectionManagerTest.php`

Expected: FAIL because native projectors do not exist and the manager routes everything to `ProductProjector`.

- [ ] **Step 3: Add shared location resolver**

Create `app/Services/ExternalSync/Projection/Concerns/ResolvesProjectionLocation.php`:

```php
<?php

namespace App\Services\ExternalSync\Projection\Concerns;

use App\Models\City;
use App\Models\Country;
use App\Models\Location;

trait ResolvesProjectionLocation
{
    protected function resolveLocation(array $raw, string $fallbackName): ?Location
    {
        $latitude = $raw['latitude'] ?? null;
        $longitude = $raw['longitude'] ?? null;

        if ($latitude === null || $longitude === null) {
            return null;
        }

        $country = Country::query()->firstOrCreate(
            ['name' => (string) ($raw['country'] ?? 'Spain')],
            ['code' => 'ES', 'continent_code' => 'EU', 'phone_code' => '+34', 'is_active' => true],
        );

        $city = City::query()->firstOrCreate(
            ['country_id' => $country->id, 'name' => (string) ($raw['city'] ?? 'Lanzarote')],
            ['is_popular' => false],
        );

        return Location::query()->updateOrCreate(
            [
                'city_id' => $city->id,
                'name' => (string) ($raw['address'] ?? $fallbackName),
            ],
            [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'description' => $raw['description'] ?? null,
                'is_popular' => false,
            ],
        );
    }
}
```

- [ ] **Step 4: Add hotel projector**

Create `app/Services/ExternalSync/Projection/HotelProjector.php`:

```php
<?php

namespace App\Services\ExternalSync\Projection;

use App\Models\Hotel;
use App\Services\ExternalSync\Projection\Concerns\ResolvesProjectionLocation;
use Illuminate\Database\Eloquent\Model;

class HotelProjector implements Projector
{
    use ResolvesProjectionLocation;

    public function project(ExternalProjectionPayload $payload): Model
    {
        $raw = $payload->raw();
        $name = (string) ($payload->payload['name'] ?? $raw['name'] ?? 'External hotel');
        $location = $this->resolveLocation($raw, $name);

        $existingId = $payload->source->syncMappings()
            ->where('resource_type', $payload->resourceType())
            ->where('external_id', $payload->externalId())
            ->value('target_id');

        return Hotel::query()->updateOrCreate(
            ['id' => $existingId],
            [
                'name' => $name,
                'description' => $payload->payload['description'] ?? $raw['description'] ?? null,
                'location_id' => $location?->id,
                'star_rating' => $raw['star_rating'] ?? null,
                'average_rating' => $raw['average_rating'] ?? 0,
                'total_ratings' => $raw['total_ratings'] ?? 0,
                'main_image_url' => $raw['image'] ?? $raw['main_image_url'] ?? null,
                'website' => $payload->payload['website'] ?? $raw['website'] ?? null,
                'phone' => $payload->payload['phone'] ?? $raw['phone'] ?? null,
                'email' => $payload->payload['email'] ?? $raw['email'] ?? null,
                'is_active' => true,
                'is_featured' => (bool) ($raw['featured'] ?? false),
            ],
        );
    }
}
```

- [ ] **Step 5: Add restaurant projector**

Create `app/Services/ExternalSync/Projection/RestaurantProjector.php` using the same idempotency pattern:

```php
<?php

namespace App\Services\ExternalSync\Projection;

use App\Models\Restaurant;
use App\Services\ExternalSync\Projection\Concerns\ResolvesProjectionLocation;
use Illuminate\Database\Eloquent\Model;

class RestaurantProjector implements Projector
{
    use ResolvesProjectionLocation;

    public function project(ExternalProjectionPayload $payload): Model
    {
        $raw = $payload->raw();
        $name = (string) ($payload->payload['name'] ?? $raw['name'] ?? 'External restaurant');
        $location = $this->resolveLocation($raw, $name);
        $existingId = $payload->source->syncMappings()
            ->where('resource_type', $payload->resourceType())
            ->where('external_id', $payload->externalId())
            ->value('target_id');

        return Restaurant::query()->updateOrCreate(
            ['id' => $existingId],
            [
                'name' => $name,
                'description' => $payload->payload['description'] ?? $raw['description'] ?? null,
                'location_id' => $location?->id,
                'cuisine' => $raw['cuisine'] ?? null,
                'price_range' => $raw['price_range'] ?? null,
                'average_rating' => $raw['average_rating'] ?? 0,
                'total_ratings' => $raw['total_ratings'] ?? 0,
                'main_image_url' => $raw['image'] ?? $raw['main_image_url'] ?? null,
                'website' => $payload->payload['website'] ?? $raw['website'] ?? null,
                'phone' => $payload->payload['phone'] ?? $raw['phone'] ?? null,
                'email' => $payload->payload['email'] ?? $raw['email'] ?? null,
                'has_reservation' => true,
                'is_active' => true,
                'is_featured' => (bool) ($raw['featured'] ?? false),
            ],
        );
    }
}
```

- [ ] **Step 6: Add tour projector**

Create `app/Services/ExternalSync/Projection/TourProjector.php`:

```php
<?php

namespace App\Services\ExternalSync\Projection;

use App\Models\Tour;
use App\Services\ExternalSync\Projection\Concerns\ResolvesProjectionLocation;
use Illuminate\Database\Eloquent\Model;

class TourProjector implements Projector
{
    use ResolvesProjectionLocation;

    public function project(ExternalProjectionPayload $payload): Model
    {
        $raw = $payload->raw();
        $name = (string) ($payload->payload['name'] ?? $raw['name'] ?? 'External tour');
        $location = $this->resolveLocation($raw, $name);
        $existingId = $payload->source->syncMappings()
            ->where('resource_type', $payload->resourceType())
            ->where('external_id', $payload->externalId())
            ->value('target_id');

        return Tour::query()->updateOrCreate(
            ['id' => $existingId],
            [
                'name' => $name,
                'description' => $payload->payload['description'] ?? $raw['description'] ?? null,
                'short_description' => $payload->payload['short_description'] ?? $raw['short_description'] ?? $payload->resourceType(),
                'location_id' => $location?->id,
                'duration_hours' => $raw['duration_hours'] ?? null,
                'duration_days' => $raw['duration_days'] ?? null,
                'base_price' => $payload->payload['price'] ?? $raw['price'] ?? 0,
                'discount_percentage' => $raw['discount_percentage'] ?? 0,
                'max_capacity' => $raw['max_capacity'] ?? 1,
                'min_participants' => $raw['min_participants'] ?? 1,
                'difficulty_level' => $raw['difficulty_level'] ?? 1,
                'average_rating' => $raw['average_rating'] ?? 0,
                'total_ratings' => $raw['total_ratings'] ?? 0,
                'main_image_url' => $raw['image'] ?? $raw['main_image_url'] ?? null,
                'is_active' => true,
                'is_featured' => (bool) ($raw['featured'] ?? false),
            ],
        );
    }
}
```

- [ ] **Step 7: Add taxi service projector**

Create `app/Services/ExternalSync/Projection/TaxiServiceProjector.php`:

```php
<?php

namespace App\Services\ExternalSync\Projection;

use App\Models\TaxiService;
use App\Services\ExternalSync\Projection\Concerns\ResolvesProjectionLocation;
use Illuminate\Database\Eloquent\Model;

class TaxiServiceProjector implements Projector
{
    use ResolvesProjectionLocation;

    public function project(ExternalProjectionPayload $payload): Model
    {
        $raw = $payload->raw();
        $name = (string) ($payload->payload['name'] ?? $raw['name'] ?? 'External taxi service');
        $location = $this->resolveLocation($raw, $name);
        $existingId = $payload->source->syncMappings()
            ->where('resource_type', $payload->resourceType())
            ->where('external_id', $payload->externalId())
            ->value('target_id');

        return TaxiService::query()->updateOrCreate(
            ['id' => $existingId],
            [
                'name' => $name,
                'description' => $payload->payload['description'] ?? $raw['description'] ?? null,
                'location_id' => $location?->id,
                'logo_url' => $raw['image'] ?? $raw['logo_url'] ?? null,
                'website' => $payload->payload['website'] ?? $raw['website'] ?? null,
                'phone' => $payload->payload['phone'] ?? $raw['phone'] ?? null,
                'email' => $payload->payload['email'] ?? $raw['email'] ?? null,
                'is_active' => true,
            ],
        );
    }
}
```

- [ ] **Step 8: Route manager to native projectors**

Update `ExternalProjectionManager` constructor:

```php
public function __construct(
    private readonly ProductProjector $productProjector,
    private readonly HotelProjector $hotelProjector,
    private readonly RestaurantProjector $restaurantProjector,
    private readonly TourProjector $tourProjector,
    private readonly TaxiServiceProjector $taxiServiceProjector,
) {}
```

Update `projectorFor()`:

```php
private function projectorFor(ExternalProjectionPayload $payload): Projector
{
    return match ($payload->targetModel()) {
        'hotel' => $this->hotelProjector,
        'restaurant' => $this->restaurantProjector,
        'tour' => $this->tourProjector,
        'taxi_service' => $this->taxiServiceProjector,
        default => $this->productProjector,
    };
}
```

- [ ] **Step 9: Run projection tests**

Run: `php artisan test tests/Feature/ExternalProjectionManagerTest.php`

Expected: PASS.

---

### Task 5: Integrate Projection Into Sync Upserts

**Files:**
- Modify: `app/Services/ExternalSync/ExternalSyncManager.php`
- Modify: `app/Services/ExternalSync/ExternalSourceSynchronizer.php`
- Test: `tests/Feature/ExternalSyncManagerTest.php`

- [ ] **Step 1: Add failing sync manager projection test**

Append to `ExternalSyncManagerTest`:

```php
public function test_catalog_upsert_projects_to_native_model_when_source_has_target_model(): void
{
    $source = $this->source();
    $source->forceFill([
        'resource_type' => 'hotel',
        'target_model' => 'hotel',
        'source_label' => 'Taxilanz Hoteles · MCP · Hoteles',
        'business_name' => 'Taxilanz Hoteles',
    ])->save();

    $manager = app(ExternalSyncManager::class);
    $manager->upsertCatalogItem($source, [
        'external_id' => 'hotel-123',
        'type' => 'hotel',
        'name' => 'Hotel Sync',
        'description' => 'Projected hotel',
        'metadata' => [
            'raw' => [
                'city' => 'Arrecife',
                'country' => 'Spain',
                'latitude' => 28.963,
                'longitude' => -13.547,
            ],
        ],
    ]);

    $this->assertDatabaseHas('hotels', ['name' => 'Hotel Sync']);
    $this->assertDatabaseHas('external_sync_mappings', [
        'external_source_id' => $source->id,
        'resource_type' => 'hotel',
        'target_model' => 'hotel',
        'external_id' => 'hotel-123',
    ]);
}
```

- [ ] **Step 2: Run sync manager tests to verify failure**

Run: `php artisan test tests/Feature/ExternalSyncManagerTest.php`

Expected: FAIL because `ExternalSyncManager` writes staging rows only.

- [ ] **Step 3: Inject projection manager**

Modify `ExternalSyncManager`:

```php
use App\Services\ExternalSync\Projection\ExternalProjectionManager;

public function __construct(
    private readonly ExternalProjectionManager $projectionManager,
) {}
```

- [ ] **Step 4: Call projection after catalog upsert**

In `upsertCatalogItem()`, store the result and project it:

```php
$item = ExternalCatalogItem::query()->updateOrCreate(
    [
        'source_platform' => $payload['source_platform'],
        'external_id' => (string) $payload['external_id'],
        'external_item_id' => $payload['external_item_id'] ?? null,
    ],
    $payload + [
        'type' => 'product',
        'last_synced_at' => now(),
    ],
);

$this->projectionManager->project($source, $item, $payload);

return $item;
```

- [ ] **Step 5: Call projection after booking and order upserts**

Apply the same pattern in `upsertBooking()` and `upsertOrder()`:

```php
$booking = ExternalBooking::query()->updateOrCreate(...);
$this->projectionManager->project($source, $booking, $payload);
return $booking;
```

```php
$order = ExternalOrder::query()->updateOrCreate(...);
$this->projectionManager->project($source, $order, $payload);
return $order;
```

- [ ] **Step 6: Ensure payload resource classification fallback**

In `ExternalSourceSynchronizer`, include source classification in normalized payloads where helpful:

```php
'resource_type' => $source->resource_type,
'target_model' => $source->target_model,
```

Add these to Woo products, Woo booking payloads, Magento products, Magento orders, and LatePoint bookings.

- [ ] **Step 7: Run sync tests**

Run: `php artisan test tests/Feature/ExternalSyncManagerTest.php tests/Feature/ExternalSourceSynchronizerTest.php`

Expected: PASS.

---

### Task 6: Source Metadata in `/explore`

**Files:**
- Modify: `app/Http/Controllers/PublicExploreController.php`
- Test: `tests/Feature/ExploreProjectedSourcesTest.php`

- [ ] **Step 1: Write failing explore test**

Create `tests/Feature/ExploreProjectedSourcesTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\ExternalSource;
use App\Models\ExternalSyncMapping;
use App\Models\Hotel;
use App\Models\Location;
use App\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExploreProjectedSourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_explore_places_include_source_metadata_for_projected_models(): void
    {
        $country = Country::query()->create(['name' => 'Spain', 'code' => 'ES']);
        $city = City::query()->create(['country_id' => $country->id, 'name' => 'Arrecife']);
        $location = Location::query()->create([
            'city_id' => $city->id,
            'name' => 'Arrecife',
            'latitude' => 28.963,
            'longitude' => -13.547,
        ]);
        $hotel = Hotel::query()->create([
            'name' => 'Hotel Sync',
            'location_id' => $location->id,
            'is_active' => true,
        ]);
        $server = Server::query()->create(['name' => 'Taxilanz Hoteles', 'slug' => 'taxilanz-hoteles']);
        $source = ExternalSource::query()->create([
            'server_id' => $server->id,
            'name' => 'Taxilanz Hoteles MCP',
            'business_name' => 'Taxilanz Hoteles',
            'source_platform' => 'mcp',
            'source_label' => 'Taxilanz Hoteles · MCP · Hoteles',
            'connection_type' => 'api',
            'resource_type' => 'hotel',
            'target_model' => 'hotel',
            'status' => 'active',
        ]);
        ExternalSyncMapping::query()->create([
            'server_id' => $server->id,
            'external_source_id' => $source->id,
            'business_name' => 'Taxilanz Hoteles',
            'source_platform' => 'mcp',
            'source_label' => 'Taxilanz Hoteles · MCP · Hoteles',
            'resource_type' => 'hotel',
            'target_model' => 'hotel',
            'target_id' => $hotel->id,
            'external_id' => 'hotel-sync',
        ]);

        $response = $this->getJson('/explore/places')->assertOk();

        $place = collect($response->json('data'))->firstWhere('id', 'hotel-'.$hotel->id);
        $this->assertSame('Taxilanz Hoteles · MCP · Hoteles', $place['source_label']);
        $this->assertSame('Taxilanz Hoteles', $place['business_name']);
        $this->assertSame('hotel', $place['resource_type']);
    }
}
```

- [ ] **Step 2: Run explore test to verify failure**

Run: `php artisan test tests/Feature/ExploreProjectedSourcesTest.php`

Expected: FAIL because `/explore/places` does not include source fields.

- [ ] **Step 3: Add source metadata helper**

Modify `PublicExploreController`:

```php
use App\Models\ExternalSyncMapping;
```

Add helper:

```php
private function sourceMetadata(string $targetModel, int $targetId): array
{
    $mapping = ExternalSyncMapping::query()
        ->where('target_model', $targetModel)
        ->where('target_id', $targetId)
        ->latest('last_synced_at')
        ->first();

    return [
        'source_label' => $mapping?->source_label,
        'business_name' => $mapping?->business_name,
        'resource_type' => $mapping?->resource_type,
    ];
}
```

- [ ] **Step 4: Merge source metadata into place arrays**

In each mapper, append:

```php
] + $this->sourceMetadata('hotel', $hotel->getKey());
```

Use aliases:

- hotels: `hotel`
- restaurants: `restaurant`
- taxi services: `taxi_service`
- tours: `tour`

- [ ] **Step 5: Run explore test**

Run: `php artisan test tests/Feature/ExploreProjectedSourcesTest.php`

Expected: PASS.

---

### Task 7: Filament Source Visibility

**Files:**
- Modify: `app/Filament/HotelAdmin/Resources/HotelResource.php`
- Modify: `app/Filament/RestaurantAdmin/Resources/RestaurantResource.php`
- Modify: `app/Filament/TourAdmin/Resources/TourResource.php`
- Modify: `app/Filament/Resources/TaxiServiceResource.php`
- Modify: `app/Filament/Resources/ExternalCatalogItemResource.php`
- Test: `tests/Feature/ProjectedFilamentResourcesTest.php`

- [ ] **Step 1: Write failing resource source visibility test**

Create `tests/Feature/ProjectedFilamentResourcesTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProjectedFilamentResourcesTest extends TestCase
{
    public function test_native_resources_include_source_columns_or_filters(): void
    {
        $files = [
            app_path('Filament/HotelAdmin/Resources/HotelResource.php'),
            app_path('Filament/RestaurantAdmin/Resources/RestaurantResource.php'),
            app_path('Filament/TourAdmin/Resources/TourResource.php'),
            app_path('Filament/Resources/TaxiServiceResource.php'),
        ];

        foreach ($files as $file) {
            $source = file_get_contents($file);
            $this->assertStringContainsString('externalSyncMappings.source_label', $source, $file);
            $this->assertStringContainsString('Source', $source, $file);
        }
    }

    public function test_external_catalog_resource_includes_resource_type(): void
    {
        $source = file_get_contents(app_path('Filament/Resources/ExternalCatalogItemResource.php'));

        $this->assertStringContainsString('resource_type', $source);
        $this->assertStringContainsString('source_label', $source);
    }
}
```

- [ ] **Step 2: Run Filament test to verify failure**

Run: `php artisan test tests/Feature/ProjectedFilamentResourcesTest.php`

Expected: FAIL because native resources do not include source fields.

- [ ] **Step 3: Add source column to native resource tables**

In each native resource table columns array, add:

```php
Tables\Columns\TextColumn::make('externalSyncMappings.source_label')
    ->label('Source')
    ->badge()
    ->toggleable(),
```

For `TourResource`, also add:

```php
Tables\Columns\TextColumn::make('externalSyncMappings.resource_type')
    ->label('Type')
    ->badge()
    ->toggleable(),
```

- [ ] **Step 4: Add source filters where relation filters are supported**

Add to filters arrays:

```php
Tables\Filters\SelectFilter::make('source')
    ->label('Source')
    ->relationship('externalSyncMappings', 'source_label'),
```

If a resource already has a broken or custom filters array, add the column first and defer the filter only if route-list fails. The source column is required in this task.

- [ ] **Step 5: Add resource type to external catalog resource**

In `ExternalCatalogItemResource`, add a table column:

```php
Tables\Columns\TextColumn::make('metadata.resource_type')
    ->label('Resource type')
    ->toggleable(isToggledHiddenByDefault: true),
```

- [ ] **Step 6: Run Filament and route checks**

Run: `php artisan test tests/Feature/ProjectedFilamentResourcesTest.php`

Expected: PASS.

Run: `php artisan route:list --except-vendor`

Expected: command exits 0 without Filament relation errors.

---

### Task 8: Final Verification

**Files:**
- All files touched in Tasks 1-7.

- [ ] **Step 1: Run focused feature tests**

Run:

```bash
php artisan test \
  tests/Feature/ExternalProjectionSchemaTest.php \
  tests/Feature/ExternalSourceRegistrarSyncTargetsTest.php \
  tests/Feature/ExternalProjectionManagerTest.php \
  tests/Feature/ExternalSyncManagerTest.php \
  tests/Feature/ExternalSourceSynchronizerTest.php \
  tests/Feature/ExploreProjectedSourcesTest.php \
  tests/Feature/ProjectedFilamentResourcesTest.php
```

Expected: PASS.

- [ ] **Step 2: Run full test suite**

Run: `php artisan test`

Expected: PASS.

- [ ] **Step 3: Run route list**

Run: `php artisan route:list --except-vendor`

Expected: exits 0.

- [ ] **Step 4: Manual smoke check**

Run:

```bash
php artisan external-sync:register-sources
php artisan route:list --path=explore --except-vendor
```

Expected:

- source registration reports the number of registered sources;
- `/explore` routes are listed;
- no fatal errors from Filament resources or model autoloading.

---

## Self-Review

- Spec coverage: the plan covers source classification, mapping table, staging-to-native projection, source metadata in `/explore`, Filament source visibility, registrar metadata, idempotency, and tests.
- Product model decision: the plan keeps wine/aloe products in `ExternalCatalogItem` through `ProductProjector`, matching the spec's open implementation decision.
- Error handling: existing `ExternalSyncManager::run()` controls source-level failure. Projection integration is isolated behind `ExternalProjectionManager`, so a follow-up can add per-record projection counters without changing adapter contracts.
- Git: commit steps are intentionally omitted because this workspace is not a Git repository.
# Nova AI + MCP Architecture

## 0. Taxonomía prioritaria: runtime capabilities vs sync connectors

Nova separa dos conceptos que no deben mezclarse en la capa de agentes:

### Runtime capabilities

Son servidores MCP o capacidades operativas pensadas para uso en tiempo real por agentes, chatbots, herramientas IDE o flujos conversacionales.

Uso:

- responder consultas operativas,
- consultar herramientas disponibles,
- ejecutar acciones en vivo,
- enrutar una petición del usuario hacia el sistema correcto,
- dar contexto al orquestador.

Ejemplos:

- `nova`
- `sirvo`
- `taxilanz_hoteles`
- `taxilanz_woo` cuando representa el MCP operativo de rutas/Chauffeur
- `lageria` si representa un MCP operativo de La Geria
- `lanzaloe` si representa un MCP operativo o wrapper runtime

Contrato recomendado:

```json
{
  "key": "taxilanz_hoteles",
  "kind": "mcp_server",
  "role": "runtime_capability",
  "usage": "agent_runtime",
  "expose_to_agents": true,
  "preferred_for_runtime": true,
  "fallback_only": false
}
```

### Sync connectors

Son endpoints directos/API usados por Nova para sincronización interna, enriquecimiento de datos o fallback técnico cuando una capacidad MCP no cubre una operación.

Uso:

- importar productos,
- importar reservas,
- refrescar catálogos,
- materializar datos externos en tablas Nova,
- complementar capacidades que no da un MCP operativo,
- fallback controlado.

No son la primera opción para agentes conversacionales.

Ejemplos:

- `lageria_woo`
- `lageria_latepoint`
- `taxilanz_woo` cuando representa WooCommerce directo
- `lanzaloe_magento`

Contrato recomendado:

```json
{
  "key": "lageria_latepoint",
  "kind": "sync_connector",
  "role": "internal_sync_connector",
  "usage": "internal_sync",
  "expose_to_agents": false,
  "preferred_for_runtime": false,
  "fallback_only": true
}
```

### Regla de prioridad

```text
Agentes y chatbots deben preferir runtime capabilities.
Sync connectors solo se usan para sincronización interna, enriquecimiento o fallback explícito.
```

En el MCP `nova`, las llamadas a sync connectors mediante `call_server` requieren `allow_internal_connector=true`.

### Tools del MCP Nova

Endpoint:

```text
https://novahubmcp.test/mcp/nova
```

Tools:

- `list_agents`: lista perfiles IA disponibles.
- `list_capabilities`: lista solo runtime capabilities/MCPs operativos.
- `list_sync_connectors`: lista solo conectores internos de sincronización.
- `list_servers`: compatibilidad; lista ambos tipos con campos de taxonomía.
- `get_server_tools`: devuelve herramientas de una capability o connector, indicando `role` y `usage`.
- `call_server`: ejecuta runtime capabilities por defecto; para connectors internos exige `allow_internal_connector=true`.

Flujo recomendado:

```text
list_capabilities
→ get_server_tools(server: taxilanz_hoteles)
→ call_server(server: taxilanz_hoteles, tool: ...)
```

Flujo interno/fallback:

```text
list_sync_connectors
→ get_server_tools(server: lageria_latepoint)
→ call_server(
    server: lageria_latepoint,
    tool: list_services,
    allow_internal_connector: true
  )
```

### Chat Gateway para canales externos

Los canales conversacionales externos no deben llamar directamente a MCPs ni sync connectors. Deben comunicarse con Nova mediante el gateway conversacional:

```text
POST /api/nova/chat
```

Uso:

- `/ai-bot`
- widgets web externos
- integraciones conversacionales futuras
- pruebas de canal que no sean WhatsApp Cloud

Payload recomendado:

```json
{
  "message": "qué puedo hacer?",
  "channel": "ai_bot",
  "conversation_id": "ai-bot-demo",
  "user": {
    "phone": "+340000001",
    "name": "Patrick",
    "locale": "es"
  },
  "context": {
    "source_url": "https://novahubmcp.test/ai-bot"
  }
}
```

Respuesta recomendada:

```json
{
  "success": true,
  "source": "nova_chat_gateway",
  "reply": "...",
  "conversation_id": "ai-bot-demo",
  "nova_request_id": 169,
  "intent": "commercial_info",
  "status": "answering_commercial_info"
}
```

El gateway llama a `NovaOrchestratorService`. El orquestador decide si debe recuperar knowledge, usar runtime capabilities MCP o, excepcionalmente, usar sync connectors como fallback interno.

Las selecciones cortas como `1`, `2`, `3`, `4` deben interpretarse según el menú conversacional anterior. Por ejemplo, después de una respuesta de planes en Lanzarote donde la opción `4` es gastronomía/restaurante, `4` debe continuar como `restaurant_booking`, no como taxi.

### Reservas de rutas taxi con CHBS + WooCommerce

Para Taxilanz, la reserva definitiva de una ruta taxi no debe crearse como un pedido WooCommerce genérico ni como una reserva operativa definitiva antes del pago.

El flujo correcto es:

```text
Nova chat / widget
→ recoge origen, destino, fecha, hora, pasajeros y contacto
→ crea una intención/pre-reserva en Nova
→ muestra o embebe el formulario de ruta taxi equivalente a CHBS
→ CHBS calcula ruta, distancia, duración, vehículo y precio
→ WooCommerce/Redsys procesa el pago
→ al pago correcto, CHBS crea la reserva definitiva
→ Nova sincroniza la reserva pagada hacia TaxiBooking / panel operativo
```

Fuente de verdad por fase:

- Antes del pago: Nova conserva una intención o pre-reserva trazable.
- Durante el checkout: CHBS + WooCommerce gestionan precio, carrito, pedido y Redsys.
- Después del pago: CHBS/WooCommerce son la fuente de verdad comercial; Nova proyecta la reserva a `TaxiBooking` para operación y seguimiento.

Regla importante:

```text
No crear Woo orders genéricos para rutas taxi.
No confirmar una reserva operativa final si el flujo requiere pago online y todavía no hay pago aprobado.
```

Si el canal es conversacional, Nova puede devolver un enlace de pago o abrir un formulario embebido con los datos pre-rellenados. La confirmación final debe llegar por webhook/sync desde WooCommerce/CHBS.

## 1. Descripción general

Nova es la capa de orquestación conversacional y operativa para conectar negocios turísticos, comercios, taxis, restaurantes y servicios locales de Lanzarote.

El objetivo de esta fase es que Nova pueda atender al cliente final desde WhatsApp o widget web, entender el contexto de la conversación y derivar hacia la acción correcta:

- informar,
- reservar,
- vender,
- recomendar,
- pedir datos,
- consultar disponibilidad,
- preparar una solicitud,
- conectar con un MCP externo,
- registrar trazabilidad comercial.

Nova no sustituye necesariamente a las plataformas existentes. Actúa como capa inteligente sobre ellas.

## 2. Principios de arquitectura

- **NovaBusiness** representa una empresa cliente.
- **NovaService** representa un servicio contratado o activado para esa empresa.
- **NovaMcpServer** conecta Nova con sistemas externos.
- **NovaWhatsappChannel** conecta WhatsApp Cloud API con la empresa/servicio.
- **NovaAiProfile** define la personalidad/configuración del asistente.
- **NovaAiKnowledge** almacena conocimiento comercial, operativo y contextual.
- **NovaRequest** registra conversaciones, solicitudes y estado operativo.

La arquitectura actual sigue el patrón:

```text
Empresa → Servicios → Capacidades → Canales / MCP / IA / Knowledge
```

## 3. Modelos principales

### NovaBusiness

Modelo: `App\Models\NovaBusiness`

Representa negocios como:

- La Geria,
- Lanzaloe,
- Taxilanz,
- El Cangrejo Rojo,
- Sirvo,
- Nova.

Campos clave:

- `name`
- `slug`
- `business_type`
- `status`
- `contact_name`
- `contact_email`
- `contact_phone`
- `website_url`
- `subscription_amount`
- `commission_rate`
- `settings`

Relaciones:

- `services()`
- `mcpServers()`
- `whatsappChannels()`
- `aiProfiles()`
- `aiKnowledge()`

### NovaService

Modelo: `App\Models\NovaService`

Representa el servicio específico contratado por una empresa.

Campos clave:

- `nova_business_id`
- `name`
- `code`
- `service_type`
- `status`
- `has_development`
- `has_maintenance`
- `has_whatsapp`
- `has_mcp`
- `has_sales`
- `has_services`
- `monthly_amount`
- `commission_rate`
- `settings`
- `notes`

Uso previsto:

- activar WhatsApp,
- activar MCP,
- activar ventas,
- activar servicios/reservas,
- definir comisiones,
- agrupar canales e inteligencia por servicio.

### NovaMcpServer

Representa una integración externa conectable por MCP o API.

Tipos actuales/conceptuales:

- Sirvo / restaurante,
- La Geria / WordPress MCP,
- Taxilanz / WooCommerce MCP,
- Lanzaloe / Magento MCP,
- Lanzaloe visitas / Laravel,
- plataforma hoteles / Laravel,
- otros sistemas externos futuros.

### NovaWhatsappChannel

Modelo: `App\Models\NovaWhatsappChannel`

Representa el canal WhatsApp Cloud API asociado a una empresa y servicio.

Campos clave:

- `nova_business_id`
- `nova_service_id`
- `name`
- `provider`
- `phone_number`
- `phone_number_id`
- `business_account_id`
- `webhook_url`
- `status`
- `credentials`
- `settings`

### NovaAiProfile

Representa el perfil de IA de una empresa/servicio.

Uso previsto:

- tono,
- idioma,
- instrucciones comerciales,
- comportamiento del asistente,
- reglas de escalado,
- prompt base.

### NovaAiKnowledge

Modelo: `App\Models\NovaAiKnowledge`

Almacena fragments de conocimiento.

Campos clave:

- `nova_business_id`
- `nova_service_id`
- `nova_ai_profile_id`
- `title`
- `content`
- `status`
- `metadata`
- `embedding`
- `vectorized_at`

Uso actual:

- información de visitas,
- cartas de restaurantes,
- tarifas de taxi,
- rutas turísticas,
- productos,
- instrucciones operativas,
- CTAs comerciales,
- enlaces relevantes.

### NovaRequest

Registra conversaciones y solicitudes generadas por el orquestador.

Uso actual:

- guardar mensaje original,
- guardar estado conversacional,
- guardar knowledge recuperado,
- guardar checks de MCP,
- guardar respuesta generada,
- mantener continuidad por teléfono.

## 4. Servicios principales

### NovaOrchestratorService

Archivo: `app/Services/Nova/NovaOrchestratorService.php`

Responsabilidad:

- recibir mensaje,
- detectar negocio,
- detectar intención,
- recuperar conversación previa,
- consultar MCPs cuando aplique,
- recuperar knowledge,
- construir respuesta final,
- registrar `NovaRequest`.

Intenciones actuales:

- `commercial_info`
- `restaurant_booking`
- `winery_visit`
- `restaurant_and_winery_visit`
- `taxi_booking`
- `unknown`

Capacidades actuales:

- menú numerado cuando la intención es ambigua,
- respuestas comerciales con CTA,
- selección de negocio por keywords,
- priorización de fragments relevantes,
- respuestas compactas para WhatsApp,
- tolerancia a MCP caído.

### NovaConversationDataExtractor

Archivo: `app/Services/Nova/NovaConversationDataExtractor.php`

Responsabilidad:

- detectar intención,
- extraer fecha,
- extraer hora,
- extraer número de personas,
- conservar contexto anterior,
- mapear respuestas numéricas del menú,
- resolver stage conversacional.

Ejemplos de detección:

```text
"qué puedo hacer en Lanzarote" → commercial_info
"quiero comer en La Cepa" → restaurant_booking
"necesito traslado al aeropuerto" → taxi_booking
"excursión en taxi por la isla" → taxi_booking
"info visitas La Geria" → commercial_info
"reservar mesa mañana a las 14 para 2" → restaurant_booking
```

### NovaKnowledgeService

Archivo: `app/Services/Nova/NovaKnowledgeService.php`

Responsabilidad:

- recibir negocio y mensaje,
- buscar knowledge activo,
- puntuar por términos,
- devolver fragments relevantes.

Estado actual:

- scoring simple por coincidencia textual,
- límite configurable,
- preparado para evolucionar a embeddings/vectorización.

### NovaWhatsAppCloudService

Archivo: `app/Services/Nova/NovaWhatsAppCloudService.php`

Responsabilidad:

- enviar mensajes por Meta WhatsApp Cloud API,
- marcar mensajes como leídos,
- enviar reacciones,
- normalizar teléfonos.

Configuración:

- `services.nova.whatsapp_phone_number_id`
- `services.nova.whatsapp_access_token`
- `services.nova.meta_graph_version`

### NovaWebsiteKnowledgeImporter

Archivo: `app/Services/Nova/NovaWebsiteKnowledgeImporter.php`

Responsabilidad:

- importar contenido básico desde `NovaBusiness.website_url`,
- limpiar HTML,
- crear fragmento en `nova_ai_knowledge`,
- asociarlo al negocio, servicio y perfil de IA cuando exista.

## 4.6 NovaAiService

Archivo: `app/Services/Nova/NovaAiService.php`

Responsabilidad:

- detectar intención del mensaje vía OpenAI (`gpt-4o-mini`).
- extraer datos de reserva estructurados desde el mensaje.
- generar respuesta conversacional natural.

Todos sus system prompts se cargan ahora desde el modelo `Prompt` de MCP vía `NovaPromptLoader`, con fallback al texto hardcoded si no están instalados.

| Método | Prompt DB | Fallback |
|---|---|---|
| `detectIntent()` | `nova-intent-detection` | hardcoded |
| `extractBookingData()` | `nova-booking-extraction` | hardcoded |
| `generateResponse()` | `nova-response-generation` | hardcoded |

## 4.7 NovaIntentExtractorService

Archivo: `app/Services/Nova/NovaIntentExtractorService.php`

Responsabilidad:

- llamar al modelo Ollama local (`qwen3:4b`) para extraer intención, fecha, hora, personas, origen y destino.
- normalizar y heredar datos de la conversación previa.
- resolver el `stage` conversacional.

El system prompt Ollama se carga desde `NovaPromptLoader::system('nova-ollama-intent')` con fallback hardcoded.

URL del modelo Ollama configurable en `services.ollama.url` (por defecto `http://ai.novagestion.eu:11434`).

## 4.8 NovaCrossSellingService

Archivo: `app/Services/Nova/NovaCrossSellingService.php`

Responsabilidad:

- devolver sugerencias de cross-selling entre negocios según el negocio actual y la intención detectada.

Matriz de reglas (ahora también editable en Filament como prompt `nova-cross-selling-rules`):

```text
la-geria + restaurant_booking → Taxilanz [high], Lanzaloe [medium]
la-geria + winery_visit       → Lanzaloe [medium], Sirvo [high]
lanzaloe + winery_visit       → La Geria [high], Taxilanz [medium]
sirvo    + restaurant_booking → La Geria [high], Taxilanz [medium]
taxilanz + taxi_booking       → La Geria [high], Sirvo [medium], Lanzaloe [medium]
```

## 4.9 NovaConversationContextService

Archivo: `app/Services/Nova/NovaConversationContextService.php`

Responsabilidad:

- cargar historial de `NovaRequest` por teléfono (caché 24h).
- detectar negocios visitados, patrones de horario, tamaño de grupo habitual.
- generar sugerencias contextuales ("la última vez reservaste para 2…").
- sugerir cross-selling basado en historial de visitas.

---

## 4.10 NovaServicesPromptCatalog ✨ nuevo

Archivo: `app/Services/NovaServicesPromptCatalog.php`

Responsabilidad:

- definir el catálogo canónico de los 6 prompts editables de `Services/Nova`.
- crear automáticamente el servidor MCP `nova-services` si no existe.
- instalar prompts en DB sin sobreescribir ediciones (`install()`).
- reinstalar resetando al contenido por defecto (`reinstall()`).

Prompts incluidos:

| Nombre DB | Servicio que lo usa | Método |
|---|---|---|
| `nova-intent-detection` | `NovaAiService` | `detectIntent()` |
| `nova-booking-extraction` | `NovaAiService` | `extractBookingData()` |
| `nova-response-generation` | `NovaAiService` | `generateResponse()` |
| `nova-ollama-intent` | `NovaIntentExtractorService` | `extract()` |
| `nova-cross-selling-rules` | `NovaCrossSellingService` | referencia documental |
| `nova-orchestrator` | `NovaOrchestratorService` | instrucciones de alto nivel |

Acciones desde Filament → `/admin/prompts`:

- **Install Nova Prompts**: instala los que no existen (seguro, preserva ediciones).
- **Reinstall Nova Prompts**: elimina y recrea todos (reset completo).

## 4.11 NovaPromptLoader ✨ nuevo

Archivo: `app/Services/NovaPromptLoader.php`

Responsabilidad:

- leer el contenido del primer mensaje `system` de un `Prompt` activo por nombre.
- cachear en memoria para no hacer N queries por request.
- devolver el `$fallback` hardcoded si el prompt no está instalado.
- limpiar caché con `NovaPromptLoader::clearCache()` (lo hacen las acciones de Filament tras instalar).

Uso en servicios:

```php
// Con fallback automático al texto hardcoded
NovaPromptLoader::system('nova-intent-detection', $this->defaultPrompt());

// Array completo de mensajes del prompt
NovaPromptLoader::messages('nova-response-generation');
```

---

## 5. Tipos de MCP e integraciones

## 5.1 Taxilanz

### Plataforma correcta

```text
Taxilanz rutas / excursiones / tarifas = WooCommerce vía MCP
```

No debe confundirse con Magento.

### Uso previsto

- rutas en taxi,
- excursiones,
- productos WooCommerce,
- pedidos,
- tarifas,
- posibles cupones internos,
- trazabilidad de origen.

### Knowledge actual preparado

Comando:

```bash
php artisan nova:seed-taxi-knowledge
```

Archivo:

```text
app/Console/Commands/NovaSeedTaxiKnowledge.php
```

Incluye:

- servicios principales,
- traslados,
- excursiones en taxi,
- contacto Taxilanz,
- tarifas desde Playa Blanca,
- tarifas desde Puerto del Carmen,
- tarifas desde Matagorda,
- tarifas desde Costa Teguise,
- tarifas desde Haría,
- tarifas desde Tinajo,
- tarifas desde La Santa Sport,
- rutas norte/sur,
- senderismo,
- recomendaciones de rutas.

Pendiente:

- crear `NovaBusiness` Taxilanz,
- asociar servicio,
- asociar MCP WooCommerce,
- ejecutar seed.

## 5.2 Plataforma de hoteles

### Plataforma correcta

```text
Hoteles / códigos / visitas / atribución = Laravel externo
```

Uso previsto:

- códigos de referencia,
- suscripciones,
- descuentos,
- atribución en tienda física,
- reservas o visitas relacionadas con hoteles,
- reporting.

Esta plataforma es distinta de WooCommerce y distinta de Magento.

## 5.3 Lanzaloe

### Plataformas correctas

```text
Lanzaloe venta online = Magento
Lanzaloe visitas / códigos / atribución física = Laravel
```

Acuerdo comercial:

- `20%` de comisión sobre compras online,
- `10%` sobre compras físicas de clientes que vayan con visita a finca reservada,
- sin setup inicial en la propuesta comercial.

Uso previsto:

- Magento para catálogo, productos, carrito, pedidos, cupones internos y ventas online,
- Laravel para visitas a finca, códigos, reservas y atribución en tienda física,
- Nova para WhatsApp, widget, recomendación y trazabilidad.

Knowledge actual:

Comando:

```bash
php artisan nova:seed-lanzaloe-knowledge
```

Archivo:

```text
app/Console/Commands/NovaSeedLanzaloeKnowledge.php
```

Incluye:

- productos,
- aloe vera,
- vinoterapia,
- categorías comerciales,
- recomendaciones.

## 5.4 La Geria

### Plataforma actual

La Geria se integra con knowledge propio y MCP/WordPress cuando aplique.

Knowledge actual:

Comando:

```bash
php artisan nova:seed-la-geria-knowledge
```

Archivo:

```text
app/Console/Commands/NovaSeedLaGeriaKnowledge.php
```

Incluye:

- visitas guiadas,
- wine tours,
- avisos de accesibilidad,
- idiomas,
- vinos,
- precios,
- historia,
- eventos,
- Taberna La Cepa.

Puntos importantes:

- Taberna La Cepa es el restaurante de Bodega La Geria.
- Visitas guiadas incluyen finca, viñedos, bodega y cata de tres vinos.
- Duración aproximada: 50 minutos.
- Precio conocido: 15€.
- Menores de 15 años gratis.
- Grupos de más de 8 personas deben contactar con la bodega por email.

## 5.5 El Cangrejo Rojo

Knowledge preparado:

Comando:

```bash
php artisan nova:seed-cangrejo-rojo-knowledge
```

Archivo:

```text
app/Console/Commands/NovaSeedCangrejoRojoKnowledge.php
```

Incluye:

- descripción,
- historia,
- contacto,
- carta,
- platos destacados,
- reservas,
- cancelaciones,
- redes sociales.

Regla importante:

```text
Nunca dar la carta completa.
```

El asistente debe:

- describir la carta,
- mencionar platos destacados,
- redirigir a la carta completa:

```text
https://www.restaurantecangrejorojo.com/la-carta/
```

Pendiente:

- crear `NovaBusiness` El Cangrejo Rojo,
- ejecutar seed,
- asociar servicio WhatsApp/MCP si aplica.

## 5.6 Sirvo / Restaurante genérico

Knowledge preparado:

Comando:

```bash
php artisan nova:seed-sirvo-restaurant-knowledge
```

Archivo:

```text
app/Console/Commands/NovaSeedSirvoRestaurantKnowledge.php
```

Uso actual:

- fallback para restaurante,
- patrón de reservas,
- disponibilidad,
- alergias,
- preferencias,
- nombre del cliente.

## 6. WhatsApp y widget

## 6.1 WhatsApp como interfaz principal

WhatsApp se considera la interfaz principal porque:

- el cliente ya lo usa,
- no requiere instalar app,
- permite conversación natural,
- facilita cierres rápidos,
- permite seguimiento,
- reduce llamadas repetitivas,
- permite CTA directo.

Ejemplo de menú:

```text
Perfecto 😊 ¿Qué te gustaría hacer?
1. Comer o reservar restaurante
2. Visitar una bodega / wine tour
3. Pedir taxi, traslado o excursión en taxi
4. Recibir información o recomendaciones
```

## 6.2 Widget web

Uso previsto:

- widget gratuito informativo,
- entrada web hacia Nova,
- generación de leads,
- conexión con WhatsApp,
- atribución de ventas/reservas.

En Lanzaloe se planteó como parte de la propuesta comercial:

```text
Widget gratuito + sin setup a cambio de comisión mayor.
```

## 7. Knowledge seeds actuales

Comandos disponibles/preparados:

```bash
php artisan nova:seed-la-geria-knowledge
php artisan nova:seed-lanzaloe-knowledge
php artisan nova:seed-sirvo-restaurant-knowledge
php artisan nova:seed-cangrejo-rojo-knowledge
php artisan nova:seed-taxi-knowledge
```

Estado:

- La Geria: preparado y ejecutado.
- Lanzaloe: preparado.
- Sirvo restaurante: preparado y ejecutado.
- Cangrejo Rojo: preparado, falta negocio.
- Taxilanz taxi: preparado, falta negocio.

## 8. Flujo conversacional actual

Entrada:

```text
WhatsApp / widget / demo artisan / ServerChat Filament
```

Proceso:

```text
Mensaje usuario
→ probar MCPs externos activos (Sirvo, La Geria)
→ recuperar conversación previa por teléfono (NovaConversationContextService)
→ NovaConversationDataExtractor:
     → NovaIntentExtractorService (Ollama, prompt nova-ollama-intent editable)
     → NovaAiService::detectIntent()  (OpenAI, prompt nova-intent-detection editable)
     → normalizar intención + stage + datos extraídos
→ resolver NovaBusiness por keywords del mensaje
→ NovaKnowledgeService: recuperar fragments relevantes
→ NovaCrossSellingService: reglas de cross-selling (editables en Filament)
→ comprobar disponibilidad si intent = restaurant_booking
→ NovaAiService::generateResponse() (OpenAI, prompt nova-response-generation editable)
     o respuestas basadas en stage/reglas PHP como fallback
→ guardar NovaRequest
```

Ejemplos:

```text
"cuánto cuesta un taxi de Puerto del Carmen a La Geria"
→ Taxilanz
→ commercial_info o taxi_booking según texto
→ knowledge tarifas taxi
```

```text
"quiero reservar mesa mañana a las 20 para 4"
→ restaurante
→ restaurant_booking
→ pedir nombre/preferencias o comprobar disponibilidad
```

```text
"qué puedo comer en Taberna La Cepa"
→ La Geria
→ commercial_info
→ knowledge Taberna La Cepa
```

```text
"quiero comprar aloe para después del sol"
→ Lanzaloe
→ commercial_info / sales intent futuro
→ Magento futuro
```

## 8.2 Chat MCP por servidor ✨ nuevo

Cada servidor MCP en Filament tiene ahora una vista de chat interactiva:

Ruta: `/admin/servers/{server}/chat`

Componentes:

### ServerChat (Livewire)

Archivo: `app/Livewire/ServerChat.php`

Capacidades:

- carga el servidor MCP y sus tools/prompts activos.
- permite seleccionar el prompt del servidor como contexto del agente.
- selección automática de tool mediante scoring heurístico sobre:
  - palabras clave del prompt activo,
  - `title` + `description` de cada tool,
  - palabras del mensaje del usuario.
- permite override manual del modo: **auto** / **forzar tool concreta**.
- ejecuta la tool seleccionada vía `ToolExecutor`.
- genera un **workflow plan** estructurado con la decisión, rationale y stages.
- muestra pasos de ejecución detallados en la UI.

### WorkflowPlanDisplay (Livewire)

Archivo: `app/Livewire/WorkflowPlanDisplay.php`

- visualiza el plan de decisión con strategy, stages y nodos.
- muestra tipo de estrategia, duración estimada, hora de generación.
- expandible/colapsable.
- portado y adaptado desde PromptlyAgent.

### Integración con prompts MCP

El chat lee el prompt activo del servidor:

```php
// Dentro de ServerChat
$prompt = $server->prompts
    ->where('is_active', true)
    ->first();

// Se expone en UI como contexto del agente
// Se usa para scoring heurístico de selección de tool
```

Los prompts del servidor se editan desde:

- `/admin/servers/{server}` → pestaña Prompts
- `/admin/prompts` → lista global

---

## 9. Filament UI Nova Businesses

La gestión de negocios Nova usa páginas laterales tipo `ManageRelatedRecords`, no relation-manager tabs.

Rutas actuales en `NovaBusinessResource`:

- `/servicios`
- `/whatsapp`
- `/mcp`
- `/ia`
- `/conocimiento-ia`

Subnavegación:

- Servicios siempre visible.
- WhatsApp visible si hay servicio con `has_whatsapp`.
- MCP visible si hay servicio con `has_mcp`.
- IA visible si hay servicio con `has_whatsapp`.
- **Conocimiento IA siempre visible** para todos los negocios (sin condición de perfil IA).

Recurso global:

- `/admin/nova-ai-knowledge` — lista todos los fragmentos de todos los negocios con filtros por negocio y estado. Permite crear, editar, borrar y hacer búsqueda por título. Disponible en el menú Nova.

## 10. Estado actual por negocio

### La Geria

Estado:

- negocio existente,
- knowledge sembrado,
- visitas/wine tours definidos,
- Taberna La Cepa definida como restaurante de la bodega,
- detección contextual corregida para `taberna` y `cepa`.

### Lanzaloe

Estado:

- knowledge preparado y ejecutado (12 fragmentos en DB),
- propuesta comercial cerrada conceptualmente,
- integración pendiente con Magento y Laravel visitas.

Acuerdo:

- 20% online,
- 10% tienda física con visita reservada.

### Taxilanz

Estado:

- seed knowledge preparado y ejecutado (17 fragmentos en DB),
- tarifas y rutas añadidas,
- falta crear negocio `taxilanz`,
- falta MCP WooCommerce de rutas/pedidos.

### El Cangrejo Rojo

Estado:

- seed knowledge preparado y ejecutado (7 fragmentos en DB),
- falta crear negocio `cangrejo-rojo`,
- reglas de carta y cancelación añadidas.

### Sirvo

Estado:

- negocio existente,
- seed genérico de restaurante ejecutado,
- usado como fallback de restaurante.

## 11. TODO técnico

### Alta de negocios

- [ ] Crear `NovaBusiness` Taxilanz.
- [ ] Crear `NovaBusiness` Lanzaloe si no existe.
- [ ] Crear `NovaBusiness` El Cangrejo Rojo.
- [ ] Revisar datos de contacto, web, tipo y estado de cada negocio.

### Servicios Nova

- [ ] Crear `NovaService` WhatsAppBot para Taxilanz.
- [ ] Crear `NovaService` WhatsAppBot para Lanzaloe.
- [ ] Crear `NovaService` WhatsAppBot para El Cangrejo Rojo.
- [ ] Definir `commission_rate` por servicio.
- [ ] Definir `has_whatsapp`, `has_mcp`, `has_sales`, `has_services` según negocio.

### Knowledge

- [x] Seeds ejecutados: La Geria (17), Taxilanz (17), Lanzaloe (12), Cangrejo Rojo (7), Sirvo (3). Total 56 fragmentos en DB.
- [x] `NovaAiKnowledgeResource` global en `/admin/nova-ai-knowledge` para editar desde Filament.
- [x] `ManageNovaBusinessAiKnowledge` visible para todos los negocios sin restricción.
- [ ] Añadir más productos reales de Lanzaloe si se obtiene catálogo completo.
- [ ] Añadir horarios reales de Cangrejo Rojo si se confirman.
- [ ] Añadir PDFs/enlaces reales de senderismo Taxilanz.
- [ ] Añadir rutas WooCommerce reales de Taxilanz.

### MCPs

- [ ] Diseñar MCP WooCommerce para Taxilanz.
- [ ] Diseñar MCP Magento para Lanzaloe ventas online.
- [ ] Diseñar integración Laravel para Lanzaloe visitas/códigos.
- [ ] Diseñar integración Laravel para plataforma hoteles/códigos.
- [ ] Normalizar tipos de `NovaMcpServer`.
- [ ] Añadir health checks robustos por tipo de MCP.
- [ ] Definir timeout/retry por integración.

### Conversación e IA

- [x] System prompts editables desde Filament (`nova-intent-detection`, `nova-booking-extraction`, `nova-response-generation`, `nova-ollama-intent`).
- [x] Cross-selling rules documentadas en prompt editable (`nova-cross-selling-rules`).
- [x] Instrucciones del orquestador en prompt editable (`nova-orchestrator`).
- [x] `NovaPromptLoader` con caché en memoria y fallback automático.
- [x] Acciones Install / Reinstall Nova Prompts en `/admin/prompts`.
- [ ] Añadir intent explícito `sales_purchase`.
- [ ] Añadir intent explícito `route_recommendation`.
- [ ] Añadir intent explícito `cancellation_request`.
- [ ] Añadir intent explícito `physical_store_visit`.
- [ ] Mejorar detección de idiomas.
- [ ] Mejorar selección de negocio cuando el mensaje no menciona marca.
- [ ] Persistir estado conversacional más estructurado.
- [ ] Evitar que preguntas informativas pidan fecha/hora/personas demasiado pronto.
- [ ] Añadir respuestas con enlaces cuando el canal permita preview.

### Atribución y comisiones

- [ ] Diseñar tabla de atribución comercial.
- [ ] Guardar `source_channel`.
- [ ] Guardar `coupon_code`.
- [ ] Guardar `external_order_id`.
- [ ] Guardar `external_visit_id`.
- [ ] Guardar importe y comisión.
- [ ] Generar informe mensual por negocio.
- [ ] Conciliar informe Nova con ventas externas.

Modelo conceptual:

```text
lead_id
nova_business_id
nova_service_id
channel
source
coupon_code
external_order_id
external_visit_id
amount
commission_rate
commission_amount
status
metadata
```

### WhatsApp / widget

- [ ] Terminar alta real de WhatsApp por negocio.
- [ ] Crear widget web embebible.
- [ ] Conectar widget con Nova.
- [ ] Añadir tracking de origen widget/WhatsApp.
- [ ] Añadir handoff humano.
- [ ] Añadir respuestas rápidas/botones si se usan templates o interactive messages.

### Filament / Operación

- [x] Chat MCP por servidor con selección automática de tool y workflow plan (`ServerChat`).
- [x] WorkflowPlanDisplay integrado en chat MCP.
- [x] Prompts editables por servidor desde `/admin/prompts` y relation manager.
- [x] Acciones Install / Reinstall Nova Prompts en `/admin/prompts`.
- [x] `NovaAiKnowledgeResource` global para editar/añadir knowledge desde `/admin/nova-ai-knowledge`.
- [x] Conocimiento IA visible siempre en subnav de cada negocio.
- [ ] Revisar pantallas laterales de Nova Businesses.
- [ ] Añadir panel de estado MCP por negocio.
- [ ] Añadir panel de conversaciones recientes.
- [ ] Añadir panel de ventas/leads atribuidos.
- [ ] Añadir filtros por negocio/servicio/status.

### Testing

- [ ] Crear tests para `NovaConversationDataExtractor`.
- [ ] Crear tests para selección de negocio.
- [ ] Crear tests para knowledge ranking.
- [ ] Crear tests para respuestas comerciales.
- [ ] Crear tests para MCP caído.
- [ ] Crear tests para cancelación Cangrejo Rojo.
- [ ] Crear tests para rutas Taxilanz.

## 12. Comandos útiles

Validar sintaxis:

```bash
php -l app/Services/Nova/NovaConversationDataExtractor.php
php -l app/Services/Nova/NovaOrchestratorService.php
```

Ejecutar seeds:

```bash
php artisan nova:seed-la-geria-knowledge
php artisan nova:seed-lanzaloe-knowledge
php artisan nova:seed-sirvo-restaurant-knowledge
php artisan nova:seed-cangrejo-rojo-knowledge
php artisan nova:seed-taxi-knowledge
```

Probar conversación:

```bash
php artisan nova:orchestrate-demo 'qué puedo hacer en lanzarote' --phone=+340000001
php artisan nova:orchestrate-demo 'quiero comer en taberna la cepa' --phone=+340000002
php artisan nova:orchestrate-demo 'cuanto cuesta un taxi de puerto del carmen a la geria' --phone=+340000003
php artisan nova:orchestrate-demo 'qué platos destacados tiene el cangrejo rojo' --phone=+340000004
php artisan nova:orchestrate-demo 'quiero una ruta sur desde playa blanca' --phone=+340000005
```

## 13. Riesgos y decisiones pendientes

### Riesgos

- Mezclar plataformas incorrectas por negocio.
- Dar cartas completas cuando debe darse resumen.
- Pedir datos de reserva en consultas solo informativas.
- No poder atribuir ventas físicas sin código o QR.
- No conciliar comisiones con ventas externas.
- Depender de MCPs externos sin fallback.

### Decisiones ya fijadas

- Taxilanz rutas/pedidos: WooCommerce MCP.
- Lanzaloe ventas online: Magento.
- Lanzaloe visitas/códigos: Laravel.
- Hoteles/códigos: Laravel externo.
- Nova: orquestación, WhatsApp, widget, IA, trazabilidad.
- La Cepa: restaurante de Bodega La Geria.
- Cangrejo Rojo: no dar carta completa; resumir y enlazar.

## 14. Próximo objetivo recomendado

Crear los negocios faltantes y ejecutar los seeds para poder probar el flujo completo con datos reales:

1. Crear Taxilanz.
2. Crear Lanzaloe.
3. Crear El Cangrejo Rojo.
4. Crear servicios WhatsApp/MCP correspondientes.
5. Probar conversaciones reales (seeds ya en DB).
6. Diseñar tabla de atribución/comisiones.
7. Empezar MCP WooCommerce Taxilanz y MCP Magento Lanzaloe.

---

## 17. Taxilanz MCP Server — Funcionalidades detalladas

El MCP Server de Taxilanz gestiona más de 180 hoteles conectados.

### Hoteles

- Listar hoteles conectados.
- Consultar hotel concreto.
- Actualizar estado de conexión.
- Ver estadísticas por hotel.

### Zonas

- Estadísticas por zona.
- Totales por zona.
- Filtros por Tías, Yaiza, Arrecife, Playa Blanca, etc.

### Reservas de taxi

- Crear reserva.
- Consultar reserva.
- Listar reservas.
- Cancelar reserva.

Datos de reserva:

- Teléfono del cliente.
- Nombre.
- Punto de recogida.
- Destino.
- Hotel.
- Fecha.
- Hora.
- Pasajeros.
- Método de pago.
- Puntos de recompensa.
- Recepcionista.

### Servicios recientes

- Últimos servicios de taxi.
- Filtros por zona.

### Conductores

- Conductores disponibles.
- Listado por estado.
- Integración prevista con Auriga.

### Mapa

- Marcadores de hoteles.
- Servicios activos.
- Localización.

### Estimaciones

- Precio estimado por ruta.
- Distancia.
- Moneda.

---

## 18. Hub de datos externos

Nova actúa no solo como bot conversacional sino como **hub de datos operativos** para plataformas externas.

Recursos Filament disponibles:

- Reservas externas (`NovaExternalBooking`).
- Pedidos externos (`NovaExternalOrder`).
- Pagos externos.
- Catálogo externo (`NovaExternalCatalogItem`).
- Fuentes externas.
- Logs de sincronización.

Casos de uso:

- Leer productos desde WooCommerce o Magento.
- Registrar reservas importadas.
- Crear pedidos.
- Sincronizar estados.
- Registrar logs de integración.
- Conectar datos con conversaciones y clientes.

---

## 19. Portal Taxista y sistema UI

El proyecto define un **Portal Taxista** con diseño propio:

- Mobile-first.
- Glass Dark UI.
- Estética SaaS premium.
- Basado en Filament, Livewire, Vite, Tailwind y Alpine.

Componentes UI definidos:

- Cards glass.
- Rows de listado.
- Badges.
- Botones CTA.
- Fondo con gradientes y grid sutil.
- Inputs glass.
- Estilo dark premium.

Funciones previstas del portal:

- Dashboard simplificado.
- Navegación por documentos.
- Acceso para taxistas.
- Solicitudes Nova.
- Command Palette / Spotlight.
- Documentación operativa.

---

## 20. Dominios funcionales

### Taxi Domain

- Taxistas, taxis, documentos, citas, tickets, gastos.
- Reservas, hoteles, servicios, conductores, localizaciones.

### HRM Domain

- Empleados, turnos, time off, asistencia, departamentos.

### Central Domain

- Departamentos, horarios, configuración de turnos, configuración global.

---

## 21. Event system y notificaciones

Nova usa eventos Laravel para desacoplar módulos.

Ejemplos de eventos previstos:

- `TaxiBookingCreated`
- `DocumentRequested`
- `TicketCreated`
- `EmployeeShiftAssigned`

Canales de notificación:

- WebSocket (tiempo real).
- Email.
- Notificaciones en base de datos.
- Push notifications (futuro).

---

## 22. Funcionalidades conversacionales futuras

### Contexto conversacional

Recordar conversaciones anteriores, preferencias, negocios visitados, patrones y reservas pasadas.

> "Como la última vez reservaste para 4, ¿es el mismo número esta vez?"

### Cambio de intención

Detectar frases como "en realidad prefiero…", "mejor…", "no, quiero…" y adaptar sin reiniciar.

### Upselling cruzado

- La Geria → Lanzaloe, Taxilanz.
- Lanzaloe → La Geria.
- Sirvo → La Geria.
- Taxilanz → La Geria, Sirvo, Lanzaloe.

> "¿Necesitas un taxi para llegar a la bodega?"

### Sugerencias proactivas

- Taxi después de cenar.
- Tour antes del aeropuerto.
- Producto relacionado tras una visita.

### Tono más humano

En vez de `"Falta la hora."` → `"¿A qué hora te viene bien?"`

### Detección de sentimiento

Adaptar tono según: positivo, negativo, urgente, indeciso, neutral.

---

## 23. Estado funcional resumido

La aplicación ya tiene base funcional para:

- Gestionar clientes Nova.
- Gestionar servicios activados.
- Configurar WhatsApp por negocio.
- Configurar MCP servers.
- Asociar perfiles de IA.
- Gestionar y editar knowledge IA desde Filament.
- Registrar solicitudes/conversaciones.
- Preparar integraciones con WooCommerce, Magento, WordPress, LatePoint, Auriga y Laravel externos.
- Operar recursos de taxi, hoteles, reservas y movilidad.
- Sincronizar entidades externas.
- Chat MCP por servidor con selección automática de tool.
- Editar prompts de IA desde Filament.
- Evolucionar hacia atribución y comisiones.

---

## 24. Descripciones del proyecto

### Descripción corta

Nova es una plataforma Laravel + Filament que actúa como hub operativo, conversacional e integrador para negocios turísticos y servicios locales de Lanzarote. Centraliza clientes, servicios, WhatsApp, IA, knowledge, reservas, taxis, comercios e integraciones externas mediante MCP, APIs y sincronizaciones.

### Descripción comercial

Nova es un sistema operativo digital para el ecosistema turístico local. Conecta taxis, bodegas, restaurantes, comercios, hoteles y servicios mediante una capa inteligente de IA, WhatsApp, widget web e integraciones MCP. La plataforma permite atender clientes, recomendar experiencias, gestionar reservas, activar ventas, sincronizar catálogos y medir la atribución comercial de cada operación.

### Descripción técnica

NovaHub MCP es una aplicación Laravel con paneles Filament que modela negocios, servicios, canales WhatsApp, perfiles IA, knowledge y MCP servers. Su arquitectura conecta empresas locales con plataformas externas como WooCommerce, Magento, WordPress, LatePoint, APIs propias y sistemas Laravel mediante una capa de orquestación conversacional. El flujo principal recibe mensajes desde WhatsApp o widget, detecta intención y negocio, consulta conocimiento, ejecuta integraciones MCP/API cuando aplica y registra solicitudes para seguimiento, reporting y atribución comercial.

---

## 15. Mapa de archivos clave ✨ nuevo

```text
app/
├── Services/
│   ├── NovaServicesPromptCatalog.php   ← catálogo de prompts editables
│   ├── NovaPromptLoader.php            ← lector de prompts con caché
│   └── Nova/
│       ├── NovaOrchestratorService.php ← punto de entrada principal
│       ├── NovaAiService.php           ← OpenAI: intent, extracción, respuesta
│       ├── NovaIntentExtractorService.php ← Ollama: extracción local
│       ├── NovaConversationDataExtractor.php ← combina ambos extractores
│       ├── NovaConversationContextService.php ← historial y patrones
│       ├── NovaKnowledgeService.php    ← fragments relevantes por keywords
│       ├── NovaCrossSellingService.php ← reglas de cross-selling
│       ├── NovaMcpClient.php           ← cliente REST + JSON-RPC para MCPs
│       ├── NovaMcpCreationService.php  ← crea reservas vía MCP
│       ├── NovaWhatsAppCloudService.php ← envía mensajes WhatsApp
│       └── NovaWebsiteKnowledgeImporter.php ← importa knowledge desde web
├── Livewire/
│   ├── ServerChat.php                 ← chat MCP por servidor en Filament
│   └── WorkflowPlanDisplay.php        ← visualización del plan de decisión
├── Filament/Resources/
│   ├── PromptResource/
│   │   └── Pages/ListPrompts.php      ← acciones Install/Reinstall Nova Prompts
│   └── ServerResource/
│       └── RelationManagers/
│           └── PromptsRelationManager.php
resources/views/livewire/
│   ├── server-chat.blade.php          ← UI del chat MCP
│   └── workflow-plan-display.blade.php
```

## 16. Dónde editar cada capa desde Filament

| Capa | URL Filament | Identificador |
|---|---|---|
| Prompt intent OpenAI | `/admin/prompts` | `nova-intent-detection` |
| Prompt extracción booking | `/admin/prompts` | `nova-booking-extraction` |
| Prompt respuesta conversacional | `/admin/prompts` | `nova-response-generation` |
| Prompt Ollama local | `/admin/prompts` | `nova-ollama-intent` |
| Reglas cross-selling | `/admin/prompts` | `nova-cross-selling-rules` |
| Instrucciones orquestador | `/admin/prompts` | `nova-orchestrator` |
| Prompts por servidor MCP | `/admin/servers/{id}` → Prompts | nombre libre |
| Herramientas por servidor | `/admin/servers/{id}` → Tools | — |
| Conocimiento por negocio | `/admin/nova-businesses/{id}/conocimiento-ia` | — |
| Conexiones MCP externas | `/admin/nova-businesses/{id}/mcp` | tipo: sirvo, la_geria… |
# Taxilanz MCP Server Documentation

## Overview

El MCP server de Taxilanz expone las funcionalidades del sistema de gestión de taxis con 180+ hoteles conectados a través del protocolo Model Context Protocol (MCP).

## Server Information

- **Name**: Taxilanz MCP Server
- **Version**: 1.0.0
- **Base URL**: `https://tu-dominio.com/api/mcp`
- **Description**: MCP server for Taxilanz taxi management system with 180+ hotels

## API Endpoints

### Server Info
```http
GET /api/mcp/info
```

### List Tools
```http
GET /api/mcp/tools
```

### Execute Tool
```http
POST /api/mcp/execute
Content-Type: application/json

{
  "name": "tool_name",
  "arguments": { ... }
}
```

## Available Tools

### Gestión de Hoteles

#### `hotel_list`
List all connected hotels with status and activity.

**Input:**
```json
{
  "status": "active",      // "active", "inactive", "all"
  "zone": "all",          // "tias", "yaiza", "arrecife", "playa_blanca", "all"
  "page": 1,
  "per_page": 50
}
```

**Output:**
```json
{
  "success": true,
  "data": {
    "hotels": [
      {
        "id": 1,
        "name": "FARO PARK ISLAND",
        "zone": 1,
        "status": "active",
        "location": {
          "lat": 28.9638,
          "lng": -13.5485,
          "address": "Calle Principal, Lanzarote"
        },
        "phone": "+34 928 XXX XXX",
        "services_today": 5,
        "services_month": 150,
        "reservations_today": 3,
        "reservations_month": 90
      }
    ],
    "summary": {
      "total_active": 57,
      "total_inactive": 0,
      "updated_at": "2026-05-21 05:00:00"
    }
  },
  "meta": {
    "total": 57,
    "page": 1,
    "per_page": 50,
    "has_more": false
  }
}
```

#### `hotel_get`
Get specific hotel details and status.

**Input:**
```json
{
  "id": 1
}
```

#### `hotel_status_update`
Update hotel connection status.

**Input:**
```json
{
  "id": 1,
  "status": "active"  // "active" or "inactive"
}
```

#### `hotel_stats_get`
Get hotel statistics (services, reservations).

**Input:**
```json
{
  "hotel_id": 1,
  "period": "today"  // "today", "week", "month", "year"
}
```

### Estadísticas por Zona

#### `zone_stats_get`
Get taxi statistics by zone (Tias, Yaiza, etc.).

**Input:**
```json
{
  "zone": "all",      // "tias", "yaiza", "arrecife", "playa_blanca", "all"
  "period": "today"   // "today", "month"
}
```

**Output:**
```json
{
  "success": true,
  "data": {
    "tias": {"today": 15, "month": 450},
    "yaiza": {"today": 12, "month": 360},
    "arrecife": {"today": 8, "month": 240},
    "playa_blanca": {"today": 5, "month": 150},
    "others": {"today": 3, "month": 90}
  }
}
```

#### `zone_total_get`
Get total taxi requests by zone.

**Input:**
```json
{
  "period": "today"  // "today", "month"
}
```

### Reservas de Taxi

#### `booking_create`
Create taxi booking with real-time Auriga API integration.

**Input:**
```json
{
  "customer_phone": "+34 646 426 442",
  "customer_name": "Patrick",
  "pickup_location": "Faro Park Island, Lanzarote",
  "dropoff_location": "Aeropuerto Lanzarote",
  "pickup_hotel_id": 1,
  "date": "2026-05-22",
  "time": "08:00",
  "passengers": 2,
  "payment_method": "card",  // "cash", "card", "revolut", "bizum"
  "use_reward_points": false,
  "receptionist_id": 123
}
```

**Output:**
```json
{
  "success": true,
  "data": {
    "booking_id": 12345,
    "auriga_booking_id": "AUR-1716273600",
    "status": "pending",
    "estimated_price": 15.50,
    "eta": "15 min"
  }
}
```

#### `booking_get`
Get specific taxi booking.

**Input:**
```json
{
  "id": 12345
}
```

#### `booking_list`
List taxi bookings with filters.

**Input:**
```json
{
  "hotel_id": 1,
  "date": "2026-05-21",
  "status": "pending",
  "customer_phone": "+34 646 426 442",
  "zone": "tias",
  "page": 1,
  "per_page": 20
}
```

#### `booking_cancel`
Cancel taxi booking.

**Input:**
```json
{
  "id": 12345
}
```

### Servicios Recientes

#### `service_list_latest`
Get latest taxi services.

**Input:**
```json
{
  "limit": 10,
  "zone": "tias"
}
```

**Output:**
```json
{
  "success": true,
  "data": [
    {
      "id": 12345,
      "hotel_name": "FARO PARK ISLAND",
      "date": "2026-05-21 08:30",
      "status": "completed",
      "driver": "Juan García"
    }
  ]
}
```

### Conductores

#### `driver_get_available`
Get available drivers from Auriga API.

**Input:**
```json
{s
  "location": "Faro Park Island, Lanzarote",
  "date": "2026-05-22",
  "time": "08:00"
}
```

**Output:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Juan García",
      "phone": "+34 612 XXX XXX",
      "license": "TAX-12345",
      "location": {
        "lat": 28.9638,
        "lng": -13.5485
      }
    }
  ]
}
```

#### `driver_list`
List all drivers with status.

**Input:**
```json
{
  "status": "available",  // "available", "busy", "offline", "all"
  "zone": "tias",
  "page": 1,
  "per_page": 20
}
```

### Mapa y Ubicaciones

#### `location_map_markers`
Get map markers for hotels and active services.

**Input:**
```json
{
  "zone": "tias",
  "show_hotels": true,
  "show_active_services": true
}
```

**Output:**
```json
{
  "success": true,
  "data": {
    "markers": [
      {
        "type": "hotel",
        "id": 1,
        "name": "FARO PARK ISLAND",
        "location": {"lat": 28.9638, "lng": -13.5485},
        "status": "active"
      },
      {
        "type": "active_service",
        "id": 12345,
        "location": {"lat": 28.9640, "lng": -13.5490},
        "status": "in_progress"
      }
    ],
    "count": 2
  }
}
```

### Estimaciones

#### `price_estimate`
Get price estimate for route.

**Input:**
```json
{
  "pickup_location": "Faro Park Island, Lanzarote",
  "dropoff_location": "Aeropuerto Lanzarote",
  "distance_km": 15
}
```

**Output:**
```json
{
  "success": true,
  "data": {
    "pickup_location": "Faro Park Island, Lanzarote",
    "dropoff_location": "Aeropuerto Lanzarote",
    "distance_km": 15,
    "estimated_price": 21.50,
    "currency": "EUR"
  }
}
```

## Configuration

### Environment Variables

Agregar al archivo `.env`:

```env
# Auriga API Configuration
AURIGA_ENDPOINT=https://api.auriga.example.com
AURIGA_API_KEY=your_auriga_api_key_here
```

## Usage Examples

### Example 1: List Active Hotels

```bash
curl -X GET "https://tu-dominio.com/api/mcp/tools" \
  -H "Accept: application/json"
```

```bash
curl -X POST "https://tu-dominio.com/api/mcp/execute" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "hotel_list",
    "arguments": {
      "status": "active",
      "per_page": 20
    }
  }'
```

### Example 2: Create Taxi Booking

```bash
curl -X POST "https://tu-dominio.com/api/mcp/execute" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "booking_create",
    "arguments": {
      "customer_phone": "+34 646 426 442",
      "customer_name": "Patrick",
      "pickup_location": "Faro Park Island, Lanzarote",
      "dropoff_location": "Aeropuerto Lanzarote",
      "date": "2026-05-22",
      "time": "08:00",
      "passengers": 2,
      "payment_method": "card"
    }
  }'
```

### Example 3: Get Zone Statistics

```bash
curl -X POST "https://tu-dominio.com/api/mcp/execute" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "zone_stats_get",
    "arguments": {
      "zone": "all",
      "period": "today"
    }
  }'
```

## Integration with Chatbot

### WhatsApp Chatbot Integration

```javascript
// Cliente AI para WhatsApp
async function handleTaxiRequest(message, phone) {
  // Detectar intent de taxi
  const intent = await detectTaxiIntent(message);
  
  if (intent.type === 'book_taxi') {
    // Crear reserva vía MCP
    const booking = await callMCPTool('booking_create', {
      customer_phone: phone,
      customer_name: intent.name,
      pickup_location: intent.pickup,
      dropoff_location: intent.dropoff,
      date: intent.date,
      time: intent.time,
      passengers: intent.passengers
    });
    
    return `✅ Taxi reservado
📍 Recogida: ${intent.pickup}
🏁 Destino: ${intent.dropoff}
⏰ ${intent.date} a las ${intent.time}
💰 Precio estimado: €${booking.data.estimated_price}`;
  }
}
```

### Receptionist Dashboard Integration

```javascript
// Dashboard para recepcionistas
async function loadHotelStats(hotelId) {
  const stats = await callMCPTool('hotel_stats_get', {
    hotel_id: hotelId,
    period: 'today'
  });
  
  return stats.data;
}

async function createBookingForGuest(hotelId, guestData) {
  const booking = await callMCPTool('booking_create', {
    pickup_hotel_id: hotelId,
    customer_phone: guestData.phone,
    customer_name: guestData.name,
    pickup_location: guestData.hotelAddress,
    dropoff_location: guestData.destination,
    date: guestData.date,
    time: guestData.time,
    receptionist_id: guestData.receptionistId
  });
  
  return booking;
}
```

## Error Handling

### Standard Error Response

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human readable error message"
  }
}
```

### Common Error Codes

- `TOOL_NOT_FOUND`: The requested tool does not exist
- `EXECUTION_ERROR`: Error during tool execution
- `VALIDATION_ERROR`: Invalid input parameters
- `NOT_FOUND`: Requested resource not found
- `UNAUTHORIZED`: Authentication required

## Rate Limiting

- **Default**: 100 requests per minute per IP
- **Burst**: 10 requests per second

## Authentication

Currently the MCP server does not require authentication. For production use, implement:

1. API Key authentication
2. JWT tokens for authenticated users
3. Rate limiting per user

## Support

For issues or questions:
- Email: support@taxilanz.com
- Documentation: https://docs.taxilanz.com/mcp
- GitHub Issues: https://github.com/taxilanz/mcp-server

## Version History

### v1.0.0 (2026-05-21)
- Initial release
- Hotel management tools
- Zone statistics
- Booking management
- Driver management
- Map markers
- Price estimation
