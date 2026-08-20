# Assets API Reference

## `list(params?)`

```typescript
client.assets.list(params?: {
  search?: string;
  type?: 'image' | 'video' | 'audio' | 'document';
  paginate?: number;
})
```

## Asset Retrieval

- `get(identifier: string)` — by UUID
- `getByFilename(filename: string)` — by filename

## Response URLs

Asset JSON includes `url`, `thumbnail_url`, and `original_url`. Append **`?variant=thumbnail`** or **`?variant=original`** when those variants exist. No API key is needed to **GET** these URLs in a browser or CDN; security is link opacity, not per-request CMS auth.

## Upload

```typescript
upload(file: File | Buffer, metadata?: {
  alt_text?: string;
  title?: string;
  caption?: string;
  description?: string;
  author?: string;
  copyright?: string;
})
```

## Delete

- `delete(identifier: string, force?: boolean)`

## Bulk Methods

- `bulkUpload(files: Array<File | Buffer>)`
- `bulkUpdateMetadata(payload: { items })`

### Bulk Metadata Item Shape

```typescript
{
  uuid: string;
  alt_text?: string;
  title?: string;
  caption?: string;
  description?: string;
  author?: string;
  copyright?: string;
}
```

## Method Summary

| Method | Signature | Description |
|--------|-----------|-------------|
| `list` | `(params?) => Promise` | List/search assets |
| `get` | `(identifier: string) => Promise` | Get asset by UUID |
| `getByFilename` | `(filename: string) => Promise` | Get asset by filename |
| `upload` | `(file: File \| Buffer, metadata?) => Promise` | Upload single file |
| `delete` | `(identifier: string, force?: boolean) => Promise` | Delete asset |
| `bulkUpload` | `(files: Array<File \| Buffer>) => Promise` | Upload multiple files |
| `bulkUpdateMetadata` | `(payload: { items }) => Promise` | Update metadata for multiple assets |
