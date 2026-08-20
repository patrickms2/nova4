# Next.js Integration Reference

## Environment Variables

```env
# Server-only (not prefixed with NEXT_PUBLIC_)
ELMAPI_PROJECT_ID=your-project-uuid
ELMAPI_API_KEY=your-api-key
# Create under Project settings → API Tokens (Sanctum PAT)

# Client-safe (prefixed) — project user auth only needs project id
NEXT_PUBLIC_ELMAPI_PROJECT_ID=your-project-uuid
```

The SDK always calls **Elmapi** at `https://your-instance.example/api`. Do not add `ELMAPI_BASE_URL` or `NEXT_PUBLIC_ELMAPI_BASE_URL`.

## App Router Data Fetching

- Use server components for reads.
- Use server actions and route handlers for writes.
- Keep the API key (`ELMAPI_API_KEY`) in server-only files.

### List response shape

See **`elmapi-content`** for the full matrix. In short: **`is_singleton: true`** collections return **one object** at the root from `list()` — not an array and not `{ data, meta }`. **`paginate` does not apply.**

With **`paginate`** on a **non-singleton** collection, the JSON body is `{ data: Entry[], meta, links }`. Without it (e.g. **`limit` only**), the body is often a **bare array** of entries. Entries always expose custom fields under **`fields`**, not `data`.

### Dynamic Route + Not Found

```tsx
import { elmapiServer } from '@/lib/noma-server';
import { notFound } from 'next/navigation';
import { NotFoundError } from '@elmapicms/js-sdk';

export default async function PostPage({ params }: { params: Promise<{ uuid: string }> }) {
  const { uuid } = await params;
  try {
    const post = await elmapiServer.content.get('blog-posts', uuid);
    return <article><h1>{post.fields.title}</h1></article>;
  } catch (error) {
    if (error instanceof NotFoundError) notFound();
    throw error;
  }
}
```

## SSG / ISR

### `generateStaticParams`

```tsx
import { elmapiServer } from '@/lib/noma-server';

export async function generateStaticParams() {
  const res = await elmapiServer.content.list('blog-posts', {
    state: 'published',
    paginate: 100,
    page: 1,
  });
  const entries = Array.isArray(res) ? res : (res as { data: Array<{ uuid: string }> }).data;
  return entries.map((post) => ({ uuid: post.uuid }));
}
```

### Time-based revalidation

```tsx
export const revalidate = 60;
```

### On-demand revalidation

```typescript
import { revalidatePath } from 'next/cache';
import { NextRequest, NextResponse } from 'next/server';

export async function POST(request: NextRequest) {
  const secret = request.headers.get('x-revalidate-secret');
  if (secret !== process.env.REVALIDATION_SECRET) {
    return NextResponse.json({ error: 'Invalid secret' }, { status: 401 });
  }
  const body = await request.json();
  revalidatePath(`/posts/${body.uuid}`);
  revalidatePath('/posts');
  return NextResponse.json({ revalidated: true });
}
```

## Social login

Complete OAuth with the provider in the browser, then **POST** the `id_token` to your own Route Handler that calls `createClient({ baseUrl, projectId }).signInWithSocial({ provider: 'google', id_token })` and sets session cookies. See [examples.md](examples.md).

## Middleware Pattern

```typescript
import { NextRequest, NextResponse } from 'next/server';

export function middleware(request: NextRequest) {
  const token = request.cookies.get('noma_access_token')?.value;
  if (!token) return NextResponse.redirect(new URL('/login', request.url));
  return NextResponse.next();
}

export const config = {
  matcher: ['/dashboard/:path*', '/account/:path*'],
};
```

## Pages Router (Legacy)

Use `getStaticProps`, `getServerSideProps`, and `getStaticPaths` with `elmapiServer` exactly as in App Router patterns: server-only token usage and published-state reads.
