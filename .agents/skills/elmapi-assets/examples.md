# Assets API Examples

## List and Search

```typescript
const assets = await client.assets.list({
  search: 'hero',
  type: 'image',
  paginate: 20,
});
```

## Get by UUID or Filename

```typescript
const byUuid = await client.assets.get('asset-uuid');
const byFilename = await client.assets.getByFilename('hero-banner.jpg');
```

## Upload from Browser

```typescript
const fileInput = document.querySelector<HTMLInputElement>('#file');
const file = fileInput!.files![0];
const result = await client.assets.upload(file, {
  alt_text: 'Product photo',
  title: 'Blue Widget',
});
```

## Upload from Node.js Buffer

```typescript
import { readFileSync } from 'fs';

const buffer = readFileSync('./image.png');
await client.assets.upload(buffer, {
  alt_text: 'Logo',
  title: 'Company Logo',
});
```

## Bulk Upload and Metadata Update

```typescript
const files: File[] = Array.from(fileInput.files!);
await client.assets.bulkUpload(files);

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
