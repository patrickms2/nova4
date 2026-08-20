# Astro Integration Reference

## Environment Variables

```env
ELMAPI_PROJECT_ID=your-project-uuid
ELMAPI_API_KEY=your-api-key
# Project settings → API Tokens (Sanctum PAT)

PUBLIC_ELMAPI_PROJECT_ID=your-project-uuid
```

The SDK always uses **Elmapi SaaS** at `https://your-instance.example/api`. Do not set `ELMAPI_BASE_URL` or `PUBLIC_ELMAPI_BASE_URL`.

Use non-`PUBLIC_` variables only on the server.

## Static Dynamic Routes

```astro
---
import { noma } from '../../lib/noma';

export async function getStaticPaths() {
  const res = await noma.content.list('blog-posts', {
    state: 'published',
    paginate: 100,
    page: 1,
  });
  // Non-singleton + paginate → use .data. Singleton collections return one object — do not use this pattern; see elmapi-content.
  const entries = Array.isArray(res) ? res : (res as { data: Array<{ uuid: string }> }).data;

  return entries.map((post) => ({
    params: { uuid: post.uuid },
    props: { post },
  }));
}

const { post } = Astro.props;
---
```

## SSR and Hybrid

```typescript
import { defineConfig } from 'astro/config';
import node from '@astrojs/node';

export default defineConfig({
  output: 'server',
  adapter: node({ mode: 'standalone' }),
});
```

```typescript
export default defineConfig({
  output: 'hybrid',
});
```

Use `export const prerender = false` on pages that must render on each request.

## Content Collections (Astro 5+)

Map Elmapi data into Astro content layer loaders and validate with `zod` schemas in `src/content.config.ts`.

## API Routes

- Webhook receiver: trigger deploy hooks or rebuild jobs.
- Content proxy: expose paginated JSON from `src/pages/api/*` routes.

## Islands

For interactive user-specific UI, use islands (`client:load`) and a browser-safe client configured with `PUBLIC_` variables and `projectUserAuth`.
