# Collections & Fields Examples

## List + Get Collection

```typescript
const collections = await client.collections.list();
const collection = await client.collections.get('blog-posts');
```

## Create Singleton and Standard Collection

```typescript
await client.collections.create({
  name: 'Blog Posts',
  slug: 'blog-posts',
  description: 'Articles and blog entries',
  is_singleton: false,
});

await client.collections.create({
  name: 'Site Settings',
  slug: 'site-settings',
  is_singleton: true,
});
```

## Update, Delete, Reorder Collections

```typescript
await client.collections.update('blog-posts', {
  name: 'Articles',
  description: 'Updated description',
});

await client.collections.reorder({
  collections: [
    { uuid: 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', order: 0 },
    { uuid: 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb', order: 1 },
  ],
});

await client.collections.delete('legacy-posts');
```

## Create, Update, Delete, Reorder Fields

```typescript
await client.fields.create('blog-posts', {
  name: 'Title',
  slug: 'title',
  type: 'text',
  required: true,
  options: { placeholder: 'Enter post title' },
});

// URL/permalink: use type 'slug' (never 'text'), attached to title.
await client.fields.create('blog-posts', {
  name: 'Slug',
  slug: 'slug',
  type: 'slug',
  required: true,
  options: {
    slug: { field: 'title', readonly: false },
  },
  validations: {
    required: { status: true, message: 'Slug is required' },
    unique: { status: true, message: 'Slug must be unique' },
  },
});

// Prefer mode: 'markdown' when seeding body copy as markdown via MCP/AI.
await client.fields.create('blog-posts', {
  name: 'Body',
  slug: 'body',
  type: 'richtext',
  required: true,
  options: {
    editor: { type: 1, mode: 'markdown', outputFormat: 'html' },
  },
});

await client.fields.update('blog-posts', 10, {
  name: 'Post Title',
  required: true,
  options: { placeholder: 'Updated placeholder' },
});

await client.fields.reorder('blog-posts', {
  fields: [
    { uuid: 'field-uuid-1', order: 0 },
    { uuid: 'field-uuid-2', order: 1 },
  ],
});

await client.fields.delete('blog-posts', 10);
```
