---
name: elmapi-astro
description: >-
  Integrates Elmapi with Astro — static builds, SSR, content collections,
  API routes, and islands. Use when building an Astro site that fetches or
  displays content from Elmapi.
---

# Elmapi + Astro

## Quick Start

```typescript
// src/lib/noma.ts
import { createClient } from '@elmapicms/js-sdk';

export const noma = createClient({
    baseUrl: process.env.ELMAPI_BASE_URL!,
  projectId: import.meta.env.ELMAPI_PROJECT_ID,
  // API key: Project settings → API Tokens
  apiKey: import.meta.env.ELMAPI_API_KEY,
});
```

Fetch content in page frontmatter:

```astro
---
// src/pages/posts/index.astro
import { noma } from '../../lib/noma';

const posts = await noma.content.list('blog-posts', {
  state: 'published',
  sort: 'created_at:desc',
  paginate: 50,
  page: 1,
});
---

<ul>
  {posts.data.map((post: { uuid: string; fields: { title?: string } }) => (
    <li><a href={`/posts/${post.uuid}`}>{post.fields.title}</a></li>
  ))}
</ul>
```

## Key Patterns

- Static mode: all data fetched at build time, fastest performance
- SSR mode: fresh data on each request, use `output: 'server'`
- Hybrid mode: default static, opt-in SSR with `export const prerender = false`
- Never expose `ELMAPI_API_KEY` to the browser (no `PUBLIC_` prefix)
- Use API routes for webhooks and pagination proxies

## References

- Full Astro integration reference: [reference.md](reference.md)
- Copy-ready snippets: [examples.md](examples.md)
- Elmapi MCP + schema-first pages: `elmapi-mcp-content-structure`
- Filters and operators: `elmapi-content`
- Error handling: `elmapi-errors`
