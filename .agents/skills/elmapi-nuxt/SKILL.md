---
name: elmapi-nuxt
description: >-
  Integrates Elmapi with Nuxt — server routes, composables, SSG/ISR, plugins,
  middleware, and Pinia stores. Use when building a Nuxt app that fetches or
  mutates content from Elmapi.
---

# Elmapi + Nuxt

## Quick Start

```typescript
// plugins/noma.client.ts
import { createClient, type TokenStorageAdapter } from '@elmapicms/js-sdk';

export default defineNuxtPlugin(() => {
  const config = useRuntimeConfig();

  const tokenStorage: TokenStorageAdapter = {
    getAccessToken: () => localStorage.getItem('noma_access_token') ?? undefined,
    setAccessToken: (t) => t ? localStorage.setItem('noma_access_token', t) : localStorage.removeItem('noma_access_token'),
    getRefreshToken: () => localStorage.getItem('noma_refresh_token') ?? undefined,
    setRefreshToken: (t) => t ? localStorage.setItem('noma_refresh_token', t) : localStorage.removeItem('noma_refresh_token'),
    clear: () => { localStorage.removeItem('noma_access_token'); localStorage.removeItem('noma_refresh_token'); },
  };

  const client = createClient({
    baseUrl: config.public.elmapiBaseUrl,
    projectId: config.public.elmapiProjectId,
    projectUserAuth: { autoRefresh: true, tokenStorage },
  });

  return { provide: { elmapi: client } };
});
```

Access in components via `useNuxtApp().$elmapi`.

Proxy content through Nitro routes:

```typescript
// server/api/posts/index.get.ts
export default defineEventHandler(async () => {
  const client = useElmapiServer();
  return client.content.list('blog-posts', {
    state: 'published',
    sort: 'created_at:desc',
    paginate: 10,
  });
});
```

## Key Patterns

- Keep `ELMAPI_API_KEY` (API key from **Project settings → API Tokens**) in server-only `runtimeConfig` (not `public`)
- Use `server/utils/` for shared server-side client
- Proxy all content through `server/api/` routes to avoid exposing the API key
- Use route rules (`isr`, `prerender`) for cache and generation strategy

## References

- Full Nuxt integration reference: [reference.md](reference.md)
- Copy-ready snippets: [examples.md](examples.md)
- Elmapi MCP + schema-first pages: `elmapi-mcp-content-structure`
- Filters and operators: `elmapi-content`
- Error handling: `elmapi-errors`
