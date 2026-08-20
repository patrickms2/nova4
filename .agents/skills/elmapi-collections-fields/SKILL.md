---
name: elmapi-collections-fields
description: >-
  Manages Elmapi collections and fields — create, update, delete, and reorder
  collections and their field schemas. Use when programmatically managing content
  schemas, field types, or collection settings.
---

# Elmapi Collections & Fields API

Collections and fields are managed via `client.collections.*` and `client.fields.*`. All operations require an API key (`apiKey`).

## Quick Start

```typescript
await client.collections.create({
  name: 'Blog Posts',
  slug: 'blog-posts',
  description: 'Articles and blog entries',
  is_singleton: false,
});
await client.collections.update('blog-posts', {
  name: 'Articles',
  description: 'Updated description',
});
await client.collections.delete('blog-posts');
await client.fields.create('blog-posts', {
  name: 'Title',
  slug: 'title',
  type: 'text',
  required: true,
  options: {
    placeholder: 'Enter post title',
  },
});
await client.fields.update('blog-posts', fieldId, {
  name: 'Post Title',
  required: true,
  options: {
    placeholder: 'Updated placeholder',
  },
});
await client.fields.delete('blog-posts', fieldId);
await client.fields.reorder('blog-posts', {
  fields: [
    { uuid: 'field-uuid-a', order: 0 },
    { uuid: 'field-uuid-b', order: 1 },
  ],
});
```

Use `collections.get()` to inspect current fields before schema updates.

## Field types (exact `type` values)

| Type | Use for |
|------|---------|
| `text` | Single-line text (titles, headings) |
| `longtext` | Multi-line plain text (excerpts, descriptions) |
| `richtext` | Formatted body copy (lexical or markdown) |
| `slug` | URL/permalink identifiers — **never use `text` for this** |
| `email` | Email with format validation |
| `password` | Encrypted password (not inside groups) |
| `number` | Integers, decimals, floats |
| `enumeration` | Dropdown from a fixed list |
| `boolean` | True/false |
| `color` | Color picker |
| `date` | Date (optional time / range) |
| `time` | Time of day |
| `media` | Asset library file(s) |
| `relation` | Link to entries in another collection |
| `json` | Raw JSON |
| `group` | Nested child fields |

**Slug fields:** use `type: 'slug'`, attach with `options.slug.field` to the source text field name (e.g. `'title'`), and set **required + unique**. See [reference.md](reference.md).

**System metadata vs custom fields:** Content entries already include **`published_at`** (and related timestamps) at the **entry root**, not inside **`fields`**. When designing a schema (blog posts, articles, pages), **do not** add a custom **`date` / `datetime` field** whose sole purpose is mirroring “published on” — use **`entry.published_at`** in the app and `sort: 'published_at:desc'` (or `created_at`) on `content.list()`. See **`elmapi-content`** (“Built-in timestamps — do not re-model as custom fields”). Add a separate date field only when editorial needs a **different** meaning than the API publish clock.

**`is_singleton` drives list API behavior:** when **`is_singleton` is `true`**, `client.content.list(slug)` returns **one entry object** at the root (not an array, not paginated `{ data, meta }`). **`paginate` does not apply** to singletons. When **`is_singleton` is `false`**, `list()` can return a **top-level array**, a **paginated** payload with **`data` + `meta` + `links`**, **`{ count }`**, or a **single** entry with **`first: true`** — see **`elmapi-content`** for the matrix. Always check **`is_singleton`** before writing fetch/parsing code.

Richtext uses **`options.editor.mode`**: `'lexical'` (default) or `'markdown'`. Write HTML/`{ html, json }` for lexical; markdown strings only when `mode` is `'markdown'`. Prefer `mode: 'markdown'` when seeding via MCP/AI. **`outputFormat`** controls API **reads** only. See [reference.md](reference.md).

## References

- Field types, options, and method matrix: [reference.md](reference.md)
- Copy-ready schema management snippets: [examples.md](examples.md)
- Error handling patterns: `elmapi-errors`
