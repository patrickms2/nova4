# Content API Reference

## `list(collectionSlug, params?)`

```typescript
client.content.list(collectionSlug: string, params?: {
  state?: 'draft' | 'published';
  locale?: string;
  exclude?: string | string[];
  where?: Record<string, unknown>;
  sort?: string;                 // 'field:asc' or 'field:desc'
  limit?: number;
  offset?: number;
  paginate?: number;
  page?: number;
  timestamps?: boolean;
  count?: boolean;
  first?: boolean;
})
```

### Singleton vs list collections (`GET /api/{collection}`)

The server branches on **`is_singleton`** from the collection record:

1. **Singleton (`is_singleton: true`)**  
   - Returns **one** `ContentEntry` JSON object at the **root** (same shape as `get()`): `uuid`, `locale`, `published_at`, `fields`.  
   - **Does not** return an array, pagination metadata, or `{ count }`. Query params **`paginate`**, **`count`**, **`limit`**, **`offset`**, and **`first` are not used** for this branch.  
   - Without `locale`, the query is scoped to the **project default locale**.  
   - **404** if no entry exists for the resolved locale/state filters.

2. **Non-singleton (`is_singleton: false`)**  
   - **`count`** (present in query): response is **`{ count: number }` only**.  
   - **`paginate`**: Laravel-style page — **`{ data: Entry[], links, meta }`** (entries in **`data`**). Use **`page`** for the page index.  
   - **Neither `paginate` nor `count`**: default is **all rows** as a **JSON array** `[ Entry, … ]` at the **root** (not wrapped in `data`).  
   - **`first: true`**: **one** `Entry` object at the **root** (first row after filters/sort).  
   - **`limit` / `offset`**: apply only when **`paginate` is not set**; **`offset` requires `limit`**.

**Agent rule:** read **`is_singleton`** from `collections.get(slug)` before interpreting `list()` results. Treating a singleton like a paginated list (e.g. expecting `response.data[0]` or `meta`) is wrong.

**Agent rule (timestamps):** each entry includes **`published_at`** at the entry root. Prefer **`entry.published_at`** and `sort` on **`published_at`** / **`created_at`** for “when did this go live?” — do **not** introduce a custom field that only mirrors that unless the product needs a different semantic (documented in **`elmapi-content`** SKILL).

**Singleton translations:** one entry per locale; **`POST /api/{collection}`** (and bulk create) **auto-links** new singleton rows to existing entries in other locales (shared **`translation_group_id`**). Use **`GET /api/{collection}/{uuid}?translation_locale=xx`** to fetch the linked row for another locale.

### Pagination

- Offset-based: `limit` + `offset` (non-singleton, no `paginate`)
- Page-based: `paginate` + `page` (non-singleton)

### Sorting

- Ascending: `price:asc`
- Descending: `created_at:desc`

## `get(collectionSlug, uuid, params?)`

```typescript
client.content.get(collectionSlug: string, uuid: string, params?: {
  locale?: string;
  translation_locale?: string;
  state?: 'draft' | 'published';
  exclude?: string | string[];
  timestamps?: boolean;
})
```

## `linkTranslation(collectionSlug, uuid, payload)`

```typescript
client.content.linkTranslation(collectionSlug: string, uuid: string, payload: {
  translation_entry_uuid: string;
})
```

**HTTP:** `POST /api/{collection}/{uuid}/link-translation` with JSON body `{ "translation_entry_uuid": "<uuid>" }`.

**Purpose:** Put two existing entries in the same collection into one **translation group** so they differ by **locale** and `get(…, { translation_locale })` can load the linked row. **422** if the target is missing, same locale as the anchor, or the anchor itself.

**Auth:** API key must include the **`update`** ability (same as entry updates).

**Response (success):** `{ message: string, translation_group_id: number }`.

Singleton creates already auto-link; use this mainly for **repeating** collections when you created locale rows separately.

## Create Payload

```typescript
{
  data: Record<string, unknown>;
  locale?: string;
  state?: 'draft' | 'published'; // 'published' publishes on create (one-step)
  published_at?: string; // ISO 8601
}
```

## Update / Patch Payload

```typescript
{
  data: Record<string, unknown>;
  locale?: string;
  published_at?: string; // ISO 8601
}
```

> Update / patch **do not** accept `state`. Saves always leave the currently published version alone. Use `content.publish(...)` / `content.unpublish(...)` to change visibility.

## `publish(collectionSlug, uuid)` / `unpublish(collectionSlug, uuid)`

```typescript
client.content.publish(collectionSlug: string, uuid: string);
client.content.unpublish(collectionSlug: string, uuid: string);
```

- `publish` mints a new immutable version from the current draft and makes it the live snapshot served under `state=published`. Returns `{ message, version_number, entry }`.
- `unpublish` clears the live pointer. All historical versions are retained and still accessible via the versions namespace. Returns `{ message }`.
- **HTTP:** `POST /api/{collection}/{uuid}/publish` and `POST /api/{collection}/{uuid}/unpublish`. Both require the `update` ability.

## Bulk Methods

- `bulkCreate(collectionSlug, { items })` — each item may include `state: 'published'` to publish on create.
- `bulkUpdate(collectionSlug, { items })` — items do **not** accept `state`; saves never change publish state. Call `publish` per UUID if needed.
- `bulkDelete(collectionSlug, { uuids, force? })`

## Versions (`client.content.versions.*`)

```typescript
client.content.versions.list(collectionSlug: string, uuid: string);
client.content.versions.get(collectionSlug: string, uuid: string, versionNumber: number);
client.content.versions.revert(collectionSlug: string, uuid: string, versionNumber: number);
client.content.versions.updateLabel(
  collectionSlug: string,
  uuid: string,
  versionNumber: number,
  payload: { label?: string | null; description?: string | null }
);
```

- `list` returns `{ data: VersionSummary[], is_draft_dirty, published_version_number }`. Newest first.
- `get` returns metadata plus the raw `snapshot` (`{ fields, meta }`). For API-rendered field output, call `content.get(..., { state: 'published' })` instead.
- `revert` restores the draft to that version's snapshot and mints a new published version (the new version is returned as `new_version_number`).
- `updateLabel` mutates only `label` / `description`; snapshot payloads remain immutable.

**State semantics (reads):** `state: 'published'` on `list`/`get` reads the currently published version's snapshot — a **404** is returned when an entry has never been published, or after `content.unpublish(...)`. `state: 'draft'` reads live draft values. Editing a published entry does not change what `state=published` returns until you call `content.publish(...)`; the dashboard shows a **Draft dirty** indicator while the draft diverges.

**Retention:** configured on the ElmapiCMS instance via `CONTENT_VERSIONS_PER_ENTRY` (default unlimited / `-1`). The currently published version is never pruned.

**Auth abilities:** `read` for `list`/`get`; `update` for `revert` and `updateLabel`.

## `where` Operators

| Operator | Description | Example |
|----------|-------------|---------|
| `eq` | Equals | `{ title: { eq: 'Hello' } }` |
| `not` | Not equals | `{ title: { not: 'Hello' } }` |
| `like` | Contains (uses `%`) | `{ title: { like: 'shirt' } }` |
| `in` | In list | `{ status: { in: 'active,pending' } }` |
| `not_in` | Not in list | `{ status: { not_in: 'archived' } }` |
| `gt` | Greater than | `{ price: { gt: 50 } }` |
| `gte` | Greater than or equal | `{ price: { gte: 50 } }` |
| `lt` | Less than | `{ price: { lt: 100 } }` |
| `lte` | Less than or equal | `{ price: { lte: 100 } }` |
| `between` | Between two values | `{ price: { between: '30,120' } }` |
| `not_between` | Outside range | `{ price: { not_between: '30,120' } }` |
| `null` | Is null | `{ published_at: { null: true } }` |
| `not_null` | Is not null | `{ published_at: { not_null: true } }` |

## OR and Relation Filters

- OR groups:
  - `where: { or: [{ category: { eq: 'shoes' } }, { category: { eq: 'boots' } }] }`
- Relation filters — outer key is the **relation field’s name** on the **current** collection; inner keys are **custom field names on the related entry** (not free-form labels). Example: if the related collection has a field slug `name`, then:
  - `where: { category: { name: 'Apparel' } }`
  If that field is slug `title`, use `{ category: { title: 'Apparel' } }` instead.

## Filterable Core Columns

`id`, `uuid`, `locale`, `state`, `created_at`, `updated_at`, `published_at`, plus custom collection fields.

## Entry JSON shape

Each entry has **`fields`**: `{ [fieldName]: value }` for custom fields. **`data`** is only the key in **request bodies** for create/update/patch (`{ data: { title: '...' } }`), not a property on returned entries.

## Relation fields

### Reads (`fields` on list/get)

- **One-to-one** (`options.relation.type === 1`): value is **`null`** or a **nested entry object** (same shape as a normal `get` response: `uuid`, `locale`, `published_at`, `fields`, …).
- **One-to-many** (`type === 2`): value is **`null`**, or an **array** of those nested entry objects (order matches the stored relation order).

Draft reads resolve links from live relation rows; `state: 'published'` hydrates from the snapshot the same way.

### Writes (`data` on create / update / patch)

Send **only** how to reference the related row(s):

- **One-to-one:** a single **UUID string**, a numeric **id**, or `null` / omit (subject to `required` and PATCH vs PUT semantics).
- **One-to-many:** an **array** of UUID strings and/or numeric ids (empty array clears the list when the field is present in the payload).

The API resolves each reference to a `ContentEntry` by **uuid** or **id**, scoped to the **project** and, when the field defines a target collection, to that collection only. Invalid references produce **422** validation on that field.

**Do not** round-trip the **nested entry object** from a `GET` as the write value (e.g. `{ uuid, fields, ... }` is not accepted — pass `entry.uuid` or `entry.id` only). If you merge `GET` output into `data`, strip relation values down to ids/uuids first.

## Richtext fields

Inspect `options.editor.mode` on the field (`collections.get(slug)`). Default is **`lexical`** if `mode` is omitted.

| `editor.mode` | Write payload | Storage |
|---------------|---------------|---------|
| **`lexical`** (default) | HTML **string**, or `{ html, json }` | HTML + Lexical JSON |
| **`markdown`** | Markdown **string** (or `{ markdown: '...' }`) | Markdown source |

- **Never** write markdown into a **lexical** field. The API may accept it, but the CMS Lexical editor can error or show broken content when the entry is opened.
- When creating richtext fields you will seed with markdown (MCP/AI), set `options.editor.mode` to **`markdown`** first.
- **Read:** controlled by `editor.outputFormat` (`html` default, or `markdown` / `lexical`). Same rule inside repeatable groups.
