# Content API Examples

## List Published Posts

```typescript
const posts = await client.content.list('blog-posts', {
  state: 'published',
  sort: 'created_at:desc',
  paginate: 10,
  page: 1,
});
// Non-singleton + paginate → { data: [...], meta, links }
```

## Singleton collection (settings, homepage, global nav)

Use **`collections.get('settings')`** and confirm **`is_singleton: true`**, then list **without** `paginate`:

```typescript
const settings = await client.content.list('settings', { state: 'published' });
// One object: settings.fields.site_name — not settings.data[0], not an array
```

With an explicit locale:

```typescript
const settingsFr = await client.content.list('settings', {
  state: 'published',
  locale: 'fr',
});
```

## Count only (non-singleton)

```typescript
const { count } = await client.content.list('products', {
  state: 'published',
  where: { in_stock: { eq: true } },
  count: true,
});
// { count: number } — not an array of entries
```

## Get a Single Entry

```typescript
const post = await client.content.get('blog-posts', 'entry-uuid', {
  state: 'published',
  locale: 'en',
});
// post.fields.title — custom fields live under `fields`, not `data`
```

## Create, Update, Patch, Delete

```typescript
// body field must use editor.mode: 'markdown' for markdown writes.
// For default lexical richtext, send HTML: body: '<p>Intro</p>' or { html, json }.
await client.content.create('blog-posts', {
  data: { title: 'My Post', body: '## Intro\n\nMarkdown body for markdown-mode richtext.' },
  state: 'draft',
  locale: 'en',
});

await client.content.update('blog-posts', 'entry-uuid', {
  data: { title: 'Updated Title', body: 'New body', category: 'tech' },
  state: 'published',
});

await client.content.patch('blog-posts', 'entry-uuid', {
  data: { title: 'Just the title changed' },
});

await client.content.delete('blog-posts', 'entry-uuid', true);
```

## Relation field writes (UUID / id only)

`GET` returns nested `fields.category` as an entry object (or array for one-to-many). `POST`/`PUT`/`PATCH` expect **references**, not that object:

```typescript
const post = await client.content.get('blog-posts', 'entry-uuid', { state: 'draft' });
const category = post.fields.category; // { uuid, locale, fields: { ... }, ... } | null

await client.content.patch('blog-posts', 'entry-uuid', {
  data: {
    category: category?.uuid ?? null, // correct
    // data: { category } — wrong: full object is not accepted
  },
});

// One-to-many: array of UUIDs
await client.content.patch('blog-posts', 'entry-uuid', {
  data: { related_tags: ['uuid-a', 'uuid-b'] },
});
```

## Link two entries as translations (repeating collection)

After creating separate rows per locale, merge them so `get` with `translation_locale` works:

```typescript
await client.content.linkTranslation('blog-posts', englishUuid, {
  translation_entry_uuid: frenchUuid,
});
// { message, translation_group_id } — API key needs `update` ability
```

## Filtering

```typescript
await client.content.list('products', {
  where: {
    category: { eq: 'shoes' },
    price: { gte: 20, lte: 100 },
    published_at: { not_null: true },
  },
});
```

## OR Group

```typescript
await client.content.list('products', {
  where: {
    or: [{ category: { eq: 'shoes' } }, { category: { eq: 'boots' } }],
  },
});
```

## Relation Filter

```typescript
await client.content.list('products', {
  where: {
    category: { name: 'Apparel' },
  },
});
```

## Bulk Operations

```typescript
await client.content.bulkCreate('blog-posts', {
  items: [
    { data: { title: 'Post 1' }, state: 'draft' },
    { data: { title: 'Post 2' }, state: 'published', locale: 'en' },
  ],
});

await client.content.bulkUpdate('blog-posts', {
  items: [
    // `state` is not a field on bulkUpdate — saves never change publish state.
    { uuid: 'uuid-1', data: { title: 'Updated 1' } },
    { uuid: 'uuid-2', data: { title: 'Updated 2' } },
  ],
});

// Publish individual UUIDs afterwards if you need the edits to go live:
await client.content.publish('blog-posts', 'uuid-1');

await client.content.bulkDelete('blog-posts', {
  uuids: ['uuid-1', 'uuid-2', 'uuid-3'],
  force: false,
});
```

## Published vs Draft reads

```typescript
// Public site: read from the most recently published snapshot.
// An entry that has never been published (or was unpublished) returns 404 here.
const live = await client.content.get('blog-posts', 'entry-uuid', { state: 'published' });

// Preview / editor: read the live working draft (includes unpublished edits).
const draft = await client.content.get('blog-posts', 'entry-uuid', { state: 'draft' });
```

## Save a draft without touching the published version

```typescript
// Update only mutates the draft — the last published version keeps serving state=published.
await client.content.patch('blog-posts', 'entry-uuid', {
  data: { title: 'Work-in-progress title' },
});

const live = await client.content.get('blog-posts', 'entry-uuid', { state: 'published' });
// -> still returns the previously published snapshot.

// When the copy is ready, publish explicitly to mint v(N+1):
await client.content.publish('blog-posts', 'entry-uuid');

// Later, take it offline without deleting history:
await client.content.unpublish('blog-posts', 'entry-uuid');
```

## Listing and labeling versions

```typescript
const { data: versions, is_draft_dirty, published_version_number } =
  await client.content.versions.list('blog-posts', 'entry-uuid');

// Annotate the current version for teammates
await client.content.versions.updateLabel('blog-posts', 'entry-uuid', published_version_number, {
  label: 'Launch copy v2',
  description: 'Approved by marketing — do not revert past this point.',
});
```

## Reverting to a previous version

```typescript
const { new_version_number } = await client.content.versions.revert(
  'blog-posts',
  'entry-uuid',
  2,
);
// Snapshot for v2 is restored into the working draft and re-published as a new version.
```

## Inspecting a version snapshot (raw payload)

```typescript
const v2 = await client.content.versions.get('blog-posts', 'entry-uuid', 2);
// v2.snapshot is the raw restoration payload: { fields: { … }, meta: { locale, translation_group_id } }
// For rendered field output (media/relations hydrated), call content.get with state: 'published'.
```
