# SDK Setup Reference

## `createClient` Options

```typescript
interface CreateClientOptions {
  baseUrl: string; // required — instance API root, e.g. https://cms.example.com/api
  projectId: string;
  apiKey?: string; // project Sanctum token (Project settings → API Tokens)
  projectUserAuth?: ProjectUserAuthConfig;
  timeout?: number; // default 30000
}
```

## Project User Auth Config

```typescript
interface ProjectUserAuthConfig {
  accessToken?: string;
  refreshToken?: string;
  autoRefresh?: boolean; // default true
  tokenStorage?: TokenStorageAdapter;
}
```

## Token Storage Adapter

```typescript
interface TokenStorageAdapter {
  getAccessToken(): string | undefined;
  setAccessToken(token?: string): void;
  getRefreshToken(): string | undefined;
  setRefreshToken(token?: string): void;
  clear(): void;
}
```

## Environment Variables

The SDK does not read environment variables directly; pass them into `createClient()`.

| Variable | Purpose |
|----------|---------|
| `ELMAPI_BASE_URL` | Instance API root (required). Browser: `NEXT_PUBLIC_ELMAPI_BASE_URL` |
| `ELMAPI_PROJECT_ID` | Project UUID. Browser: `NEXT_PUBLIC_ELMAPI_PROJECT_ID` |
| `ELMAPI_API_KEY` | Project Sanctum token from **Project settings → API Tokens** (server-only; same as MCP) |

```env
ELMAPI_BASE_URL=https://your-instance.example/api
ELMAPI_PROJECT_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
ELMAPI_API_KEY=your-project-api-token
```

## Client API Surface

| Namespace / Method | Purpose | Auth Required |
|--------------------|---------|---------------|
| `client.content.*` | Content CRUD, bulk ops, filtering, publish/unpublish/`discardDraft`, versions, `linkTranslation` | API key (`apiKey`; mutate/publish needs **`update`**) |
| `client.assets.*` | Asset CRUD, upload, bulk ops | API key |
| `client.collections.*` | Collection management | API key |
| `client.fields.*` | Field management | API key |
| `client.signUp()` | User registration | None |
| `client.signInWithPassword()` | User login (password) | None |
| `client.signInWithSocial()` | User login (OIDC `id_token`, e.g. Google) | None |
| `client.me()` | Current user | User auth token |
| `client.signOut()` | Logout | User auth token |

## Project locales (REST API)

**Read:** `GET /api/` (same base URL and headers as the SDK) returns **`locales`** (string array) and **`default_locale`**. Use this to align your app’s i18n config with the CMS.

**Mutate** (requires API key with **`admin`** ability — same as collection/field schema changes):

| Method | Path | Body | Result |
|--------|------|------|--------|
| `POST` | `/project/locales` | `{ "locale": "tr" }` | Updated project JSON (`locales`, `default_locale`, …) |
| `DELETE` | `/project/locales/{locale}` | — | Updated project JSON (cannot delete the **default** locale) |
| `PUT` | `/project/locales/default` | `{ "locale": "en" }` | Sets default; **adds** the locale to the list if it was missing |

Locale codes are normalized to **lowercase** server-side. For a multilingual app (e.g. Next.js with `en`, `tr`, `es`), **create these locales in Elmapi before** relying on `locale` in content APIs.

If `@elmapicms/js-sdk` does not yet wrap these routes, call them with the same **`Authorization`**, **`project-id`**, and **`Content-Type: application/json`** headers you use for other API requests.

**Elmapi MCP:** use `add_project_locale` and `set_default_project_locale` only; **locale removal is not exposed in MCP** (use dashboard or `DELETE` via REST if needed).

## Content & asset responses (quick reference)

- Entries use **`fields`** for custom values (`entry.fields.title`). Request bodies still use `{ data: { ... } }` for writes.
- **`content.list`** shape depends on **`is_singleton`** (from `collections.get(slug)`) and query params:
  - **Singleton collection:** **one object** at the root `{ uuid, locale, fields, … }` — **not** an array, **not** `{ data, meta }`. **`paginate` / `count` / `first` are ignored** by the API for this case.
  - **Non-singleton + `paginate`:** `{ data: Entry[], links, meta }`.
  - **Non-singleton, no `paginate`, no `count`:** **top-level array** `[ Entry, … ]`.
  - **Non-singleton + `count`:** `{ count: number }` only.
  - **Non-singleton + `first`:** one **Entry** object at the root.
- When the collection type is unknown, branch on **`is_singleton`** first; do not assume every `list()` result is paginated or is an array.
- Richtext: write format follows `editor.mode` (default **lexical** → HTML or `{ html, json }`; **markdown** mode → markdown string). Never write markdown into a lexical field. Read follows `editor.outputFormat`.
- Asset **`url`** / **`thumbnail_url`** / **`original_url`**: (optional `?variant=`). See skill **`elmapi-assets`**.

## Package Exports

```typescript
import {
  createClient,
  type TokenStorageAdapter,
  type ProjectUserAuthConfig,
  type CreateClientOptions,
  type AuthSession,
  type AuthStateChangeEvent,
  ApiClient,
  ElmapiError,
  AuthenticationError,
  AuthorizationError,
  NotFoundError,
  ValidationError,
  RateLimitError,
  ServerError,
  NetworkError,
  TimeoutError,
} from '@elmapicms/js-sdk';
```
