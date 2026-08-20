# Server External Sync Design

## Context

MCP Studio already manages dynamic MCP servers in `servers`, `tools`, `resources`, and `prompts`. The seeded servers include business metadata for Sirvo, La Geria, Taxilanz, and Lanzaloe, and several of them represent remote WooCommerce, LatePoint, and Magento systems.

The previous project at `/Users/patrickms/Downloads/taxilanzhrnew` contains useful synchronization code for WooCommerce, LatePoint, and Magento. MCP Studio has partial Nova service code copied over, but it does not have the Nova model and migration layer required to run those services directly.

The goal is to import the useful synchronization behavior without forcing the whole Nova business module into MCP Studio. The local admin should show where each synchronized record came from. For example, product/catalog views must allow La Geria WooCommerce, Lanzaloe Magento, and Taxilanz WooCommerce records to coexist in one table while keeping their source visible and filterable.

## Selected Approach

Use MCP Studio servers as the integration boundary.

Each sync-capable `Server` can have one or more local external sources attached to it. Sync actions run from the server and write normalized records into shared local tables. Filament resources display the normalized records with source metadata, so records from multiple platforms and businesses can coexist.

This avoids importing the full Nova business model and keeps the feature aligned with the existing MCP Studio architecture.

## Data Model

### `external_sources`

Stores one external integration source per server/platform/business combination.

Key fields:

- `server_id`
- `name`, for example `La Geria Woo + LatePoint DB`
- `business_name`, for example `La Geria`
- `source_platform`, for example `woo`, `latepoint`, `magento`, `sirvo`
- `connection_type`, for example `api`, `database`, `mcp`
- `base_url`, `api_url`
- database connection fields when needed
- encrypted credentials where needed
- `settings`
- `status`
- `last_sync_started_at`, `last_sync_finished_at`, `last_sync_failed_at`, `last_sync_error`

### `external_catalog_items`

Stores products, routes, visits, bookable services, and other sellable catalog items.

Key fields:

- `server_id`
- `external_source_id`
- `business_name`
- `source_platform`
- `source_label`, for example `La Geria · Woo`, `Lanzaloe · Magento`, `Taxilanz · Woo`
- `external_id`, `external_item_id`
- `type`, for example `product`, `route`, `guided_visit`, `service`
- `status`
- `name`, `description`, `short_description`
- `sku`
- `price`, `regular_price`, `sale_price`, `currency`
- `booking_url`, `purchase_url`, `admin_url`
- `metadata`
- `source_updated_at`, `source_fingerprint`, `last_synced_at`

### `external_bookings`

Stores restaurant bookings, LatePoint reservations, route/tour reservations, and WooCommerce orders that represent bookings.

Key fields:

- `server_id`
- `external_source_id`
- `business_name`
- `source_platform`
- `source_label`
- `external_id`, `external_item_id`
- `intent_key`
- `booking_type`, for example `restaurant`, `latepoint`, `route`, `tour`, `order`
- `status`, `payment_status`
- customer fields: name, email, phone
- service fields: service name, start/end time, party size, quantity
- totals and currency
- `admin_url`
- `metadata`
- `source_updated_at`, `source_fingerprint`, `last_synced_at`

### `external_orders`

Stores WooCommerce and Magento orders when they should not be represented as bookings.

Key fields:

- `server_id`
- `external_source_id`
- `business_name`
- `source_platform`
- `source_label`
- `external_id`, `external_increment_id`
- `status`, `payment_status`
- customer fields
- subtotal, tax, shipping, discount, grand total, currency
- items JSON
- `admin_url`
- `metadata`
- `source_updated_at`, `source_fingerprint`, `last_synced_at`

### `external_sync_logs`

Stores sync run summaries.

Key fields:

- `external_source_id`
- `server_id`
- `command`
- `sync_type`, for example `catalog`, `bookings`, `orders`, `mixed`
- `status`
- processed, created, updated, skipped
- `summary`
- `error`
- timestamps

## Sync Behavior

Each server can expose sync actions in Filament:

- `Sync local`: incremental sync.
- `Full sync`: full sync with confirmation.
- `Register source`: creates or updates `external_sources` from server metadata and environment variables.

The synchronization layer adapts logic from:

- `NovaWooLatePointDatabaseSyncService`
- `NovaWooCommerceApiSyncService`
- `NovaMagentoApiSyncService`
- `NovaRegisterExternalIntegrations`

The adapted services must depend on `ExternalSource` instead of Nova models. They should write to the shared `external_*` tables and always set `server_id`, `external_source_id`, `business_name`, `source_platform`, and `source_label`.

Incremental sync uses the source's last successful sync time with a small overlap window to avoid missing updates.

Failures are recorded on `external_sources.last_sync_error` and in `external_sync_logs`.

## Filament UI

### Server Resource

`ServerResource` should show source and sync status:

- source stack from `metadata.source_stack`
- business from `metadata.business`
- last sync status from related `external_sources`
- actions for `Register source`, `Sync local`, and `Full sync`

Server detail pages should expose related external sources and recent sync logs.

### External Catalog Items Resource

A new admin resource lists all synchronized catalog records together.

Required columns:

- name
- type
- price/currency
- status
- source label
- business name
- server
- last synced at

Required filters:

- business
- source platform
- source label
- server
- type
- status

Records from La Geria WooCommerce, Lanzaloe Magento, and Taxilanz WooCommerce must be visible in the same table, with source labels making the origin clear.

### External Bookings Resource

A new admin resource lists synchronized bookings from Sirvo, LatePoint, WooCommerce route bookings, and other sources.

Required columns:

- customer
- booking type
- service name
- scheduled time
- status
- payment status
- source label
- business name
- server
- last synced at

Required filters:

- business
- source platform
- source label
- server
- booking type
- status
- payment status

### External Orders Resource

A new admin resource lists WooCommerce and Magento orders that are not normalized as bookings.

Required columns:

- external order number
- customer
- total/currency
- status
- payment status
- source label
- business name
- server
- ordered at
- last synced at

## Source Labels

Source labels must be stable and human readable.

Examples:

- `La Geria · Woo`
- `La Geria · LatePoint`
- `Lanzaloe · Magento`
- `Taxilanz · Woo`
- `Sirvo · Reservas`

The source label is stored on each normalized record so historical rows remain understandable even if a server or source is renamed later.

## Environment Configuration

The initial import supports the environment groups already used in `taxilanzhrnew`:

- `WOOCOMMERCE_*`
- `NOVA_TAXILANZ_WOO_*`
- `NOVA_LAGERIA_DB_*`
- `NOVA_LANZALOE_MAGENTO_*`

The registration command maps those groups to `external_sources` attached to the matching MCP Studio servers. Matching is based on server slug, business metadata, and source stack.

## Error Handling

Sync actions must not expose secrets in Filament notifications, logs, or command output.

Each sync run should:

- mark the source as started
- record created/updated/skipped counts
- mark the source as finished or failed
- persist a sync log
- show a concise Filament notification

If one source fails, other sources in a multi-source sync should still run where possible.

## Testing

Testing should focus on behavior rather than remote systems.

Required tests:

- migrations create the expected external sync tables
- registering sources from metadata/env creates the correct `external_sources`
- catalog upsert preserves `server_id`, `business_name`, `source_platform`, and `source_label`
- bookings from different sources coexist and remain filterable
- sync failure records `last_sync_error` and a failed sync log
- Filament table definitions include source columns and filters

Remote API and database calls should be tested with fakes or local fixtures.

## Out Of Scope

This design does not import the full Nova business module.

This design does not merge external products into existing restaurant menu, tour, taxi, or travel package tables. The first implementation stores synchronized external data in normalized external tables and makes it visible in Filament.

This design does not create new remote bookings or orders. It only imports/synchronizes remote data to local storage.
