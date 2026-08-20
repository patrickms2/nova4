# MCP Resource Projection Design

## Context

The public `/explore` page already unifies local `Hotel`, `Restaurant`, `TaxiService`, and `Tour` records. It does not read from the new `external_*` synchronization tables.

The current external sync foundation stores source identity and normalized records in `external_sources`, `external_catalog_items`, `external_bookings`, and `external_orders`. That is useful for audit and coexistence, but it does not make synchronized records appear in the domain-specific Filament resources.

The next step is to let every MCP server endpoint declare the kind of business resource it provides, then project synchronized records into the correct local model.

## Goal

Use option A: keep external staging records for traceability, then create or update native local models so synchronized data appears in the correct Filament resource and in `/explore`.

Examples:

- Taxilanz Hoteles MCP hotel endpoint creates or updates `Hotel` records.
- Sirvo or LatePoint restaurant reservations create or update restaurant booking records.
- Taxilanz route products create or update `Tour` records classified as routes.
- Tour visit endpoints create or update `Tour` records classified as visits.
- Chauffeur or taxi endpoints create or update `TaxiService` and taxi booking records.
- La Geria WooCommerce wine products and Lanzaloe Magento aloe products coexist in product/catalog resources with visible source labels.

## Resource Classification

Each sync-capable endpoint must carry a target classification. Platform alone is not enough because WooCommerce can represent products, route bookings, restaurant reservations, or other business objects.

Add endpoint/source metadata with these fields:

- `resource_type`: domain type such as `hotel`, `restaurant`, `tour_route`, `tour_visit`, `taxi`, `restaurant_booking`, `tour_booking`, `taxi_booking`, `travel_booking`, `wine_product`, `aloe_product`, `generic_product`.
- `target_model`: local Eloquent model class or stable alias, such as `hotel`, `restaurant`, `tour`, `taxi_service`, `restaurant_booking`, `tour_booking`, `taxi_booking`, `travel_booking`, `external_catalog_item`.
- `sync_direction`: initially `remote_to_local`.
- `source_label`: human-readable origin, such as `La Geria · Woo · Vinos` or `Taxilanz · Woo · Rutas`.
- `capability`: the MCP capability or endpoint name used to fetch the data.

`ExternalSource` remains the integration boundary, but it must be specific enough to represent one source/platform/resource target combination.

## Recommended Data Model

Add a projection mapping table instead of adding source columns to every business table.

### `external_sync_mappings`

Stores the link between a remote record and its local projected model.

Fields:

- `id`
- `server_id`
- `external_source_id`
- `business_name`
- `source_platform`
- `source_label`
- `resource_type`
- `target_model`
- `target_id`
- `external_id`
- `external_item_id`, nullable
- `payload_hash`, nullable
- `last_synced_at`
- timestamps

Indexes:

- unique: `external_source_id`, `resource_type`, `external_id`, `external_item_id`
- index: `target_model`, `target_id`
- index: `resource_type`, `source_platform`

This keeps native tables cleaner while still allowing every Filament resource to show a source column by joining or resolving the mapping.

## Sync Flow

1. Server action runs `registerExternalSources` or `syncExternalSources`.
2. Registrar reads server metadata and creates one `ExternalSource` per endpoint/resource target.
3. Adapter fetches remote records from WooCommerce, LatePoint, Magento, Sirvo, or a custom MCP endpoint.
4. Sync manager writes normalized staging rows to `external_catalog_items`, `external_bookings`, or `external_orders`.
5. Projection service selects a projector based on `resource_type`.
6. Projector upserts the native model and writes/updates `external_sync_mappings`.
7. Existing Filament resources and `/explore` see the native models naturally.

## Projectors

Each projector has one job: convert normalized external payloads into the local model shape.

Initial projectors:

- `HotelProjector`: creates or updates `Hotel`, ensures a `Location` exists when coordinates or address are available.
- `RestaurantProjector`: creates or updates `Restaurant`, location and contact fields.
- `TourProjector`: creates or updates `Tour`; supports route and visit classification through `resource_type`.
- `TaxiServiceProjector`: creates or updates `TaxiService`.
- `RestaurantBookingProjector`: creates or updates `RestaurantBooking` where enough local restaurant mapping exists; otherwise creates `PublicBookingRequest` with source metadata.
- `TourBookingProjector`: creates or updates `TourBooking` where enough tour mapping exists; otherwise creates `PublicBookingRequest`.
- `TaxiBookingProjector`: creates or updates `TaxiBooking` where enough taxi service mapping exists; otherwise creates `PublicBookingRequest`.
- `ProductProjector`: keeps product/catalog records in external catalog initially unless a native product model is confirmed.

Projectors must be idempotent. Running a full sync twice must update the same local records, not duplicate them.

## Filament Changes

Each relevant resource should expose origin:

- Hotel resource: source label column/filter.
- Restaurant resource: source label column/filter.
- Tour resource: source label column/filter, plus route/visit filter where available.
- Taxi service resource: source label column/filter.
- Booking resources: source label and external reference where records are imported.
- Catalog/product resources: business name, source platform, source label.

The source should be visible in tables and view pages. Forms may keep the source read-only.

## `/explore` Changes

`/explore` can continue to read native models.

Add source metadata to place payloads when a mapping exists:

- `source_label`
- `business_name`
- `resource_type`

The public API should expose this metadata so search and debugging can distinguish Taxilanz, La Geria, Lanzaloe, and other MCP sources. The UI can decide separately whether to render the source label publicly.

## Front Remote Booking Creation

Public booking requests are still stored locally first as `PublicBookingRequest` records. After the local request is saved, the app may attempt a remote booking creation when the selected local model has an `external_sync_mappings` row connected to a supported source.

Supported creation targets:

- Sirvo restaurant requests: POST to `api/reservations` with the mapped remote restaurant id.
- LatePoint tour visits: POST to `wp-json/wp-abilities/v1/abilities/latepoint/create-booking/run` with the mapped remote service id.

Remote creation stores its result on the public request:

- `remote_booking_status`: `created`, `skipped`, or `failed`.
- `remote_source_platform`
- `remote_source_label`
- `remote_external_id`
- `remote_response`
- `remote_error`

Remote failure must not block the local request. The manager should still be able to review the locally created request and retry or resolve it manually.

## Server Metadata

Extend `LocalMcpServersSeeder` definitions so each `action_tool` or endpoint can declare its sync target.

Example shape:

```php
'sync_targets' => [
    [
        'capability' => 'woocommerce_orders',
        'source_platform' => 'woo',
        'resource_type' => 'tour_route',
        'target_model' => 'tour',
        'source_label_suffix' => 'Rutas',
    ],
]
```

The registrar should prefer explicit `sync_targets`. Existing `source_stack` inference remains a fallback for older server definitions.

## Error Handling

Projection errors must not erase staging records.

Behavior:

- Adapter failure marks the source sync as failed.
- Staging upsert failure marks the source sync as failed.
- Projection failure is logged per record and increments `skipped` or `failed_projected`.
- Other records from the same source should continue where possible.
- `external_sync_logs.summary` should include projection counts.

## Testing

Feature tests should cover:

- registering explicit endpoint sync targets from server metadata;
- projecting a hotel payload to `Hotel`;
- projecting a restaurant payload to `Restaurant`;
- projecting route and visit payloads to `Tour` with distinct `resource_type`;
- projecting taxi service payloads to `TaxiService`;
- idempotency: second sync updates the same local record;
- source visibility in Filament table definitions;
- `/explore/places` includes projected records and source metadata.

## Non-Goals

- Do not replace the `/explore` controller with a new search index in this iteration.
- Do not remove existing `external_*` resources.
- Do not implement general bidirectional sync yet; only explicit front booking creation is allowed for supported sources.
- Do not assume every WooCommerce product is a generic product; each endpoint/source must define its resource target.

## Open Implementation Decision

Native product storage is not yet clear in this codebase. Until a canonical product model is confirmed, wine and aloe product synchronization should continue to use `ExternalCatalogItem` with clear `business_name`, `source_platform`, `source_label`, and `resource_type`.
