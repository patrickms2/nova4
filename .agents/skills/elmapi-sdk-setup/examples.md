# SDK Setup Examples

## Server-Side Client (API key)

```typescript
import { createClient } from '@elmapicms/js-sdk';

export const serverClient = createClient({
    baseUrl: process.env.ELMAPI_BASE_URL!,
  projectId: process.env.ELMAPI_PROJECT_ID!,
  apiKey: process.env.ELMAPI_API_KEY!,
});
```

## Browser Client (Project User Auth)

```typescript
import { createClient } from '@elmapicms/js-sdk';

export function createBrowserClient(tokenStorage: TokenStorageAdapter) {
  return createClient({
    baseUrl: process.env.NEXT_PUBLIC_ELMAPI_BASE_URL!,
    projectId: process.env.NEXT_PUBLIC_ELMAPI_PROJECT_ID!,
    projectUserAuth: {
      autoRefresh: true,
      tokenStorage,
    },
  });
}
```

## `localStorage` Token Adapter

```typescript
const localStorageAdapter: TokenStorageAdapter = {
  getAccessToken: () => localStorage.getItem('noma_access_token') ?? undefined,
  setAccessToken: (token) => token
    ? localStorage.setItem('noma_access_token', token)
    : localStorage.removeItem('noma_access_token'),
  getRefreshToken: () => localStorage.getItem('noma_refresh_token') ?? undefined,
  setRefreshToken: (token) => token
    ? localStorage.setItem('noma_refresh_token', token)
    : localStorage.removeItem('noma_refresh_token'),
  clear: () => {
    localStorage.removeItem('noma_access_token');
    localStorage.removeItem('noma_refresh_token');
  },
};
```

## Cookie Token Adapter

Install dependency:

```bash
npm install js-cookie
```

```typescript
import Cookies from 'js-cookie';

const cookieAdapter: TokenStorageAdapter = {
  getAccessToken: () => Cookies.get('noma_access_token'),
  setAccessToken: (token) => token
    ? Cookies.set('noma_access_token', token, { sameSite: 'Lax' })
    : Cookies.remove('noma_access_token'),
  getRefreshToken: () => Cookies.get('noma_refresh_token'),
  setRefreshToken: (token) => token
    ? Cookies.set('noma_refresh_token', token, { sameSite: 'Lax' })
    : Cookies.remove('noma_refresh_token'),
  clear: () => {
    Cookies.remove('noma_access_token');
    Cookies.remove('noma_refresh_token');
  },
};
```

## Debug Info Check

```typescript
console.log(serverClient.getDebugInfo());
```
