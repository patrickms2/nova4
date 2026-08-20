---
name: elmapi-content
description: >-
  Works with Elmapi content entries — list, get, create, update, patch,
  delete, bulk operations, and optional translation linking. Use when building pages that display, filter,
  paginate, or mutate content from Elmapi collections.
---

# Elmapi Content API

All methods are on `client.content.*`. Requires an API key (`apiKey` from **Project settings → API Tokens**).

## Response shape

List and get responses expose each entry as **`uuid`**, **`locale`**, **`published_at`**, and **`fields`** (an object of custom field values). There is no per-entry `data` property for field values — use **`entry.fields.title`**, not `entry.data.title`.

### Built-in timestamps — do not re-model as custom fields

Every entry already exposes **`published_at`** (and the API supports sorting by timestamps such as **`created_at`** / **`published_at`** on `list()` — see **`reference.md`**). **Do not** add redundant collection fields such as **`published-at`**, **`publish-date`**, or **`posted-at`** whose only job is “when this post went live.” That duplicates system metadata, drifts out of sync with **`publish()` / `unpublish()`**, and confuses implementers.

**Only** add a separate date field when the product **explicitly** needs a value that **must differ** from Elmapi’s publish time (for example a backdated “display byline date” or a scheduled **teaser** date with different semantics). Name it clearly (e.g. **`display-date`**) and document why it is not `entry.published_at`.

**Richtext fields** follow `options.editor.mode`. CMS UI default is **`lexical`**; **Elmapi MCP** defaults omitted mode to **`markdown`** when creating/updating fields. On **create/update/patch**: for **lexical**, send an **HTML string** or `{ html, json }` — **never markdown** (it breaks the CMS editor). For **`mode: 'markdown'`**, send a **markdown string**. On **read**, shape follows `editor.outputFormat`. See **`elmapi-collections-fields`**.

## List response by collection type (critical)

**Always** check the collection’s **`is_singleton`** flag (via `client.collections.get(slug)` or your schema). The **same** `client.content.list(slug, …)` call returns **different JSON shapes**:

| Collection | HTTP / JSON shape | Notes |
|------------|---------------------|--------|
| **`is_singleton: true`** | **One object** at the root: `{ uuid, locale, published_at, fields }` | **Not** an array. **Not** Laravel pagination (`data` + `meta` + `links`). **`paginate`, `count`, `limit`/`offset`, and `first` do not apply** — the server handles singletons in a separate branch and returns the single entry (or **404**). |
| **`is_singleton: false`** + **`paginate`** | **`{ data: Entry[], links, meta }`** | Page with `page` query param. Entries are in **`data`**. |
| **`is_singleton: false`** (no `paginate`) | **Top-level JSON array** `[ Entry, … ]` | All matching rows (subject to `limit`/`offset` when both are set). |
| **`is_singleton: false`** + **`first: true`** | **One object** at the root (same as `get`) | First matching row only. |
| **`is_singleton: false`** + **`count`** (any value) | **`{ count: number }`** | Count only — **no entries**. Evaluated only for non-singleton collections. |

**Singleton locale:** if you omit `locale` on a singleton list, the API uses the **project default locale**. Pass `locale` explicitly to read another language’s singleton row.

**Do not** pass `paginate` for singleton collections expecting pagination metadata — it is **ignored** server-side; you still receive a **single entry object**.

### Singleton + multiple project locales

For **`is_singleton: true`**, the CMS allows **at most one entry per locale**. When you **create** entries via the API (including **bulk create**), new rows are **automatically linked** into one **translation group** so `GET …/{uuid}?translation_locale=…` resolves sibling locales. You do not need a separate “link translation” call for normal creates. Duplicate **`POST`** for the same locale still returns **422**.

**Repeating collections (`is_singleton: false`):** entries are **not** auto-linked across locales. After you have two rows (e.g. English and French posts), call **`client.content.linkTranslation(slug, anchorUuid, { translation_entry_uuid: otherUuid })`** — `POST /api/{collection}/{uuid}/link-translation` — so `translation_locale` on **`get`** resolves the sibling. Requires an API key with **`update`** ability. The API only **links**; it does not expose unlink (use the dashboard).

## Quick Start

```typescript
const posts = await client.content.list('blog-posts', {
  state: 'published',
  sort: 'created_at:desc',
  paginate: 10,
});
```

Singleton example (e.g. site settings slug `settings`):

```typescript
const settings = await client.content.list('settings', { state: 'published' });
// `settings` is one entry object: settings.fields.site_name, not settings[0], not settings.data
```

## Core Operations

```typescript
const item = await client.content.get('blog-posts', 'entry-uuid');

await client.content.create('blog-posts', {
  data: { title: 'My Post', body: '## Markdown body\n\nParagraph.' },
  state: 'draft', // 'published' on create is allowed (publish-on-create)
});

await client.content.patch('blog-posts', 'entry-uuid', {
  data: { title: 'Updated title' },
});

await client.content.delete('blog-posts', 'entry-uuid');
```

## Save vs. Publish (always two steps for existing entries)

**Saves never change publish state.** `content.update`, `content.patch`, and `content.bulkUpdate` only mutate the draft fields and mark the draft as dirty. They never mint a new version and never unpublish. `state` is no longer part of the update payload.

To change visibility, use the explicit actions:

```typescript
await client.content.patch('blog-posts', 'entry-uuid', {
  data: { title: 'New title' }, // draft-only; state=published keeps serving the last snapshot
});

// Mint a new version from the current draft and make it live:
await client.content.publish('blog-posts', 'entry-uuid');

// Hide the entry from state=published reads (versions are retained):
await client.content.unpublish('blog-posts', 'entry-uuid');

// Discard unpublished draft changes (restore draft from current published version):
await client.content.discardDraft('blog-posts', 'entry-uuid');
```

`state: 'published'` is still accepted by `content.create` / `content.bulkCreate` — publishing on initial creation is a single-call flow.

## Draft / Published reads (versioning)

Elmapi follows a working-draft + published-snapshot model:

- `state: 'published'` on `list`/`get` reads from the **most recently published version snapshot** — immutable, safe for public sites. An entry that has **never been published** (or was later unpublished) returns **404** under `state=published`, even if a draft exists.
- `state: 'draft'` returns entries whose `state` column is `draft` (live draft field values).
- Editing a published entry does **not** change what `state=published` returns until you call `content.publish()`. The dashboard shows a **Draft dirty** indicator while the draft diverges. Draft field values for a still-published entry are edited in the dashboard; public `state=published` keeps serving the last snapshot.

```typescript
const publicCopy = await client.content.get('blog-posts', 'entry-uuid', { state: 'published' });
// After draft edits: publicCopy.fields still match the last publish until content.publish()
```

## Versions

Every publish creates an immutable version. Use the versions namespace to audit and roll back:

```typescript
const versions = await client.content.versions.list('blog-posts', 'entry-uuid');
const v2 = await client.content.versions.get('blog-posts', 'entry-uuid', 2);
await client.content.versions.revert('blog-posts', 'entry-uuid', 2); // mints v3 from v2
await client.content.versions.updateLabel('blog-posts', 'entry-uuid', 2, {
  label: 'Launch copy v2',
  description: 'Approved by marketing.',
});
```

Version retention is configured on your ElmapiCMS instance (`CONTENT_VERSIONS_PER_ENTRY`, default unlimited); the currently published version is always kept.

## Filtering

Use a `where` object on `list()`:

```typescript
await client.content.list('products', {
  where: {
    category: { eq: 'shoes' },
    price: { gte: 20, lte: 100 },
  },
});
```

Supported operators include `eq`, `not`, `like`, `in`, `gt`, `gte`, `lt`, `lte`, `between`, `null`, and `not_null`.

## Bulk Operations

Use bulk methods for batches:

```typescript
await client.content.bulkCreate('blog-posts', { items: [{ data: { title: 'Post 1' } }] });
await client.content.bulkUpdate('blog-posts', { items: [{ uuid: 'uuid-1', data: { title: 'Updated' } }] });
await client.content.bulkDelete('blog-posts', { uuids: ['uuid-1', 'uuid-2'] });
```

## Project locales (i18n)

Content requests use a **`locale`** query/body field only for locale codes that exist on the project. **Configure locales on the project** (dashboard **Localization**, or **`POST /api/project/locales`** with an **`admin`** API key — see **`elmapi-sdk-setup`**) before building a multi-language Next.js or other app.

## References

- Full API signatures and parameter tables: [reference.md](reference.md)
- Copy-ready query and mutation snippets: [examples.md](examples.md)
- Error handling patterns: `elmapi-errors`
