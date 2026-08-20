---
name: elmapi-nextjs
description: >-
  Integrates Elmapi with Next.js — server components, SSG/ISR, server actions,
  route handlers, auth (password + social id_token), and middleware. Use when building a Next.js app that
  fetches or mutates content from Elmapi.
---

# Elmapi + Next.js

App Router is the primary focus.

## Quick Start

Use separate clients for server and browser contexts:

```typescript
// lib/noma-server.ts — server-side only, never imported in client components
import { createClient } from '@elmapicms/js-sdk';

export const elmapiServer = createClient({
    baseUrl: process.env.ELMAPI_BASE_URL!,
  projectId: process.env.ELMAPI_PROJECT_ID!,
  // API key from Project settings → API Tokens (not an end-user uak_ key)
  apiKey: process.env.ELMAPI_API_KEY!,
});
```

```typescript
// lib/noma-client.ts — browser-safe, no apiKey
import { createClient } from '@elmapicms/js-sdk';

export function createBrowserClient() {
  return createClient({
    baseUrl: process.env.NEXT_PUBLIC_ELMAPI_BASE_URL!,
    projectId: process.env.NEXT_PUBLIC_ELMAPI_PROJECT_ID!,
    projectUserAuth: {
      autoRefresh: true,
      tokenStorage: localStorageAdapter, // see elmapi-sdk-setup
    },
  });
}
```

Fetch in an async server component:

```tsx
// app/posts/page.tsx
import { elmapiServer } from '@/lib/noma-server';

export default async function PostsPage() {
  const posts = await elmapiServer.content.list('blog-posts', {
    state: 'published',
    sort: 'created_at:desc',
    paginate: 10,
  });

  return (
    <ul>
      {posts.data.map((post: { uuid: string; fields: { title?: string } }) => (
        <li key={post.uuid}>{post.fields.title}</li>
      ))}
    </ul>
  );
}
```

## Key Patterns

- Server components: direct `await` calls, no hooks
- Keep `ELMAPI_API_KEY` server-only (no `NEXT_PUBLIC_` prefix)
- Use `notFound()` from `next/navigation` when catching `NotFoundError` in dynamic pages
- Prefer route handlers or server actions for mutations

## References

- Full Next.js integration reference: [reference.md](reference.md)
- Copy-ready snippets: [examples.md](examples.md)
- Elmapi MCP + schema-first pages (any framework): `elmapi-mcp-content-structure`
- SDK setup and auth context: `elmapi-sdk-setup` and `elmapi-user-auth`
- Filtering and operators: `elmapi-content`
- Error patterns: `elmapi-errors`
