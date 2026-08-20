---
name: elmapi-assets
description: >-
  Manages Elmapi assets — list, get, upload, delete, and bulk operations for
  files and media. Use when uploading images, managing file metadata, or
  working with the Elmapi asset library.
---

# Elmapi Assets API

All methods are on `client.assets.*`. Requires an API key (`apiKey` from **Project settings → API Tokens**).

## Quick Start

```typescript
const assets = await client.assets.list({
  search: 'hero',
  type: 'image',
  paginate: 20,
});
```

## Core Operations

```typescript
const asset = await client.assets.get('asset-uuid');
const result = await client.assets.upload(file, {
  alt_text: 'Product photo',
  title: 'Blue Widget',
});
await client.assets.delete('asset-uuid');
```

Upload accepts `File` (browser) or `Buffer` (Node.js). `getByFilename()` is available when UUID is unknown.

## Public URLs (headless)

The API returns `url`, `thumbnail_url`, and `original_url` as **stable, cacheable links**. Optional query params: `?variant=thumbnail` (images) and `?variant=original` when an original file exists. These routes do **not** require authentication; anyone with the URL can fetch the file (opaque link — not a substitute for row-level permissions in your app).

## Bulk Operations

```typescript
const results = await client.assets.bulkUpload(files);
await client.assets.bulkUpdateMetadata({
  items: [
    {
      uuid: 'asset-uuid-1',
      alt_text: 'Updated alt text',
      title: 'New title',
    },
    {
      uuid: 'asset-uuid-2',
      caption: 'Photo by Jane Doe',
      copyright: '© 2025 Jane Doe',
    },
  ],
});
```

## References

- Full signatures and metadata schema: [reference.md](reference.md)
- Copy-ready upload/search/bulk snippets: [examples.md](examples.md)
- Error handling patterns: `elmapi-errors`
