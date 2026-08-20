# Next.js Examples

## Server Component Listing

```tsx
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

## Server Action Create

```tsx
import { elmapiServer } from '@/lib/noma-server';
import { redirect } from 'next/navigation';

async function createPost(formData: FormData) {
  'use server';
  const result = await elmapiServer.content.create('blog-posts', {
    data: {
      title: formData.get('title') as string,
      body: formData.get('body') as string,
    },
    state: 'draft',
  });
  redirect(`/posts/${result.uuid}`);
}
```

## Route Handler: Elmapi social login (forward `id_token`)

After Google (or another supported provider) OAuth completes, call Elmapi with the **`id_token`** using a server-only client (no `apiKey`). Set cookies from the response.

```typescript
import { NextRequest, NextResponse } from 'next/server';
import { createClient } from '@elmapicms/js-sdk';

export async function POST(request: NextRequest) {
  const { id_token: idToken, nonce } = await request.json();
  if (!idToken || typeof idToken !== 'string') {
    return NextResponse.json({ message: 'id_token required' }, { status: 400 });
  }

  const client = createClient({
    baseUrl: process.env.ELMAPI_BASE_URL!,
    projectId: process.env.ELMAPI_PROJECT_ID!,
  });

  const result = await client.signInWithSocial({
    provider: 'google',
    id_token: idToken,
    ...(nonce ? { nonce: String(nonce) } : {}),
  });

  const res = NextResponse.json({ user: result.user });
  res.cookies.set('noma_access_token', result.access_token ?? '', { httpOnly: true, sameSite: 'lax', path: '/' });
  res.cookies.set('noma_refresh_token', result.refresh_token ?? '', { httpOnly: true, sameSite: 'lax', path: '/' });
  return res;
}
```

## Route Handler Webhook

```typescript
import { revalidatePath } from 'next/cache';
import { NextRequest, NextResponse } from 'next/server';

export async function POST(request: NextRequest) {
  const payload = await request.json();
  if (payload.collection_slug && payload.uuid) {
    revalidatePath(`/${payload.collection_slug}/${payload.uuid}`);
    revalidatePath(`/${payload.collection_slug}`);
  }
  return NextResponse.json({ ok: true });
}
```
