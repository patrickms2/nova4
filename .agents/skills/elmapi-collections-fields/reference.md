# Collections & Fields Reference

## Collections Methods

| Method | Signature | Description |
|--------|-----------|-------------|
| `list` | `() => Promise` | List all collections |
| `get` | `(slug: string) => Promise` | Get collection with fields |
| `create` | `(payload: Record<string, unknown>) => Promise` | Create collection |
| `update` | `(slug: string, payload: Record<string, unknown>) => Promise` | Update collection |
| `delete` | `(slug: string) => Promise` | Delete collection |
| `reorder` | `(payload: { collections: { uuid: string; order: number }[] }) => Promise` | Reorder collections (`uuid` + `order`, min 0) |

## Fields Methods

| Method | Signature | Description |
|--------|-----------|-------------|
| `create` | `(slug: string, payload: Record<string, unknown>) => Promise` | Add field to collection |
| `update` | `(slug: string, fieldId: number \| string, payload: Record<string, unknown>) => Promise` | Update field |
| `delete` | `(slug: string, fieldId: number \| string) => Promise` | Delete field |
| `reorder` | `(slug: string, payload: { fields: { uuid: string; order: number }[] }) => Promise` | Reorder fields (`uuid` + `order`, min 0) |

## Notes

- There is no standalone `fields.list()`.
- Use `collections.get(slug)` to inspect fields.
- `fieldId` accepts `number | string` where applicable.

## Field types

Exact `type` string values (16 total). There is no `select` type — use `enumeration`.

| Type | Description | Key options |
|------|-------------|-------------|
| `text` | Single-line text (titles, headings) | `repeatable`, `hideInContentList`, `hiddenInAPI` |
| `longtext` | Multi-line plain text | `repeatable`, `hideInContentList`, `hiddenInAPI` |
| `richtext` | Formatted long-form content | `editor.mode` (`lexical` \| `markdown`), `editor.outputFormat` — see below |
| `slug` | URL/permalink; can auto-generate from a text field | `slug.field` (source field **name**), `slug.readonly` |
| `email` | Email with format validation | `repeatable`, `hideInContentList`, `hiddenInAPI` |
| `password` | Encrypted password; **not** allowed inside groups | `hideInContentList`, `hiddenInAPI` |
| `number` | Integer, decimal, float | `repeatable`, `hideInContentList`, `hiddenInAPI` |
| `enumeration` | Dropdown from a fixed list | `enumeration.list: string[]`, `multiple` |
| `boolean` | True/false | `hideInContentList`, `hiddenInAPI` |
| `color` | Color picker | `repeatable`, `hideInContentList`, `hiddenInAPI` |
| `date` | Calendar date | `includeTime`, `mode: 'single' \| 'range'`, `repeatable` |
| `time` | Time of day | `repeatable`, `hideInContentList`, `hiddenInAPI` |
| `media` | Asset library file(s) | `media.type`: `1` single, `2` multiple |
| `relation` | Link to entries in another collection | `relation.collection`, `relation.type` (`1`/`2`), `includeDraft` — see below |
| `json` | Raw JSON | `hideInContentList`, `hiddenInAPI` |
| `group` | Nested child fields | `repeatable` **required**; create children with `parent_field_id` |

### Slug fields (`type: 'slug'`)

URL/permalink fields **must** use `type: 'slug'`, not `text`. Attach to a title/name field and set required + unique:

```typescript
await client.fields.create('blog-posts', {
  name: 'Slug',
  slug: 'slug',
  type: 'slug',
  required: true,
  options: {
    slug: {
      field: 'title', // source text field name
      readonly: false,
    },
  },
  validations: {
    required: { status: true, message: 'Slug is required' },
    unique: { status: true, message: 'Slug must be unique' },
  },
});
```

## Singleton Collections

Use `is_singleton: true` for one-entry configuration content (for example site settings, single homepage document per locale).

**Content API:** `GET /api/{slug}` for a singleton returns **one JSON object** (the entry), not a list and not pagination. **`paginate` and `count` query params do not apply.** Omitting `locale` uses the project default locale. See skill **`elmapi-content`** (“List response by collection type”).

## Richtext field options

Richtext storage depends on **`editor.mode`** (default **`lexical`**). When creating or updating a `richtext` field:

```typescript
options: {
  editor: {
    type: 1,
    mode: 'lexical' | 'markdown', // default: lexical
    outputFormat: 'html' | 'markdown' | 'lexical', // API/read behavior
  },
}
```

- **`mode: 'lexical'`** — CMS Lexical editor (CMS UI default when mode is omitted). On **write**, send an HTML string or `{ html, json }`. **Do not** send markdown.
- **`mode: 'markdown'`** — markdown source storage. On **write**, send a markdown string. **Elmapi MCP** defaults omitted `mode` to **markdown** on create/update field and create_collection nested fields.
- **`outputFormat`** — how the Content API **returns** the field on read (`html` default). It does not change the write format.

Changing `mode` on an existing field does not convert stored values. Match write payloads to the field’s current mode.

## Relation fields (`type: 'relation'`)

Relation fields point to **other content entries** in the same project. Define them with `options.relation`:

```typescript
await client.fields.create('products', {
  name: 'Category',
  slug: 'category',
  type: 'relation',
  required: false,
  options: {
    relation: {
      /** Target collection: positive integer id, or slug / name string (normalized server-side to an id) */
      collection: 'categories',
      /** 1 = one-to-one (single related entry), 2 = one-to-many (ordered list) */
      type: 1,
    },
    /** Optional: when true, relation pickers / queries may include draft entries (dashboard behavior). */
    includeDraft: false,
  },
});
```

- After create/update, the API stores `options.relation.collection` as a **numeric collection id** when the target resolves inside the project (slug/name strings are accepted on input).
- **Cardinality:** `type: 1` — reads return **one** nested entry object (or `null`). `type: 2` — reads return an **array** of nested entry objects.
- Content **writes** must send **entry UUID strings** and/or **numeric entry ids**, not full entry objects from `GET` responses. See **`elmapi-content`** (“Relation fields” on reads vs writes).
