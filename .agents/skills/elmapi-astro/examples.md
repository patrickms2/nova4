# Astro Examples

## SSR Detail Page with Not Found Handling

```astro
---
import { noma } from '../../lib/noma';
import { NotFoundError } from '@elmapicms/js-sdk';

const { uuid } = Astro.params;

try {
  var post = await noma.content.get('blog-posts', uuid!);
} catch (error) {
  if (error instanceof NotFoundError) return Astro.redirect('/404');
  throw error;
}
---
```

## API Route Proxy

```typescript
import type { APIRoute } from 'astro';
import { noma } from '../../../lib/noma';

export const GET: APIRoute = async ({ url }) => {
  const page = url.searchParams.get('page') || '1';
  const posts = await noma.content.list('blog-posts', {
    state: 'published',
    paginate: 10,
    page: parseInt(page, 10),
  });

  return new Response(JSON.stringify(posts), {
    headers: { 'Content-Type': 'application/json' },
  });
};
```

## Island Greeting

```astro
---
import UserGreeting from '../components/UserGreeting';
---

<UserGreeting client:load />
```
