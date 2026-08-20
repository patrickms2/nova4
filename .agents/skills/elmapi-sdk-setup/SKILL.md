---
name: elmapi-sdk-setup
description: >-
  Sets up and configures the ElmapiCMS JS SDK client. Use when installing
  @elmapicms/js-sdk, creating a client, configuring baseUrl, project API tokens,
  environment variables, or troubleshooting SDK connectivity.
---

# Elmapi SDK Setup

## Installation

```bash
npm install @elmapicms/js-sdk
```

## Quick Start

```typescript
import { createClient } from '@elmapicms/js-sdk';

const client = createClient({
  baseUrl: 'https://your-instance.example/api',
  projectId: 'your-project-uuid',
  apiKey: 'your-project-api-token',
  timeout: 30000, // optional, defaults to 30000ms
});
```

`baseUrl` is **required** (self-hosted instance API root). `apiKey` is a **project** Sanctum token from **Project settings → API Tokens**.

## Authentication Modes

### Project API token (server/admin / CMS writes)

```typescript
const client = createClient({
  baseUrl: process.env.ELMAPI_BASE_URL!,
  projectId: process.env.ELMAPI_PROJECT_ID!,
  apiKey: process.env.ELMAPI_API_KEY!,
});
```

Prefer env names shared with the MCP server: `ELMAPI_BASE_URL`, `ELMAPI_API_KEY`, `ELMAPI_PROJECT_ID`.

### Project user auth (browser/end-user identity)

```typescript
const client = createClient({
  baseUrl: process.env.NEXT_PUBLIC_ELMAPI_BASE_URL!,
  projectId: process.env.NEXT_PUBLIC_ELMAPI_PROJECT_ID!,
  projectUserAuth: {
    autoRefresh: true,
    tokenStorage: myTokenStorage,
  },
});
```

Project Auth JWTs are **identity-only**. Do **not** use end-user tokens for CMS content writes that should appear as dashboard/API authorship. For user-generated CMS content (comments, etc.), use a **BFF** (server route) with the **project API token**, and store author identity in content fields (e.g. `author-name`, `author-user-id` from `me()`).

## Project locales (multilingual apps)

Configure **which locale codes exist** on the project **before** creating translated entries. **Read** `locales` / `default_locale` via **`GET /api/`** (`client.project.get()`). **Add or change** them with `client.project.locales.add/remove/setDefault` (requires **`admin`** on the project token). Elmapi MCP exposes **`add_project_locale`** and **`set_default_project_locale`** only (locale removal is dashboard or SDK/REST — not MCP).

## Security Rules

- Never expose `ELMAPI_API_KEY` to browser code.
- Use `apiKey` (project token) for server-side content/schema/admin API calls and BFF writes.
- Use `projectUserAuth` for end-user sessions (password; social `id_token` when enabled on the instance).
- Prefer exchanging provider `id_token` for Elmapi session tokens on the **server** (Route Handler / server route), not from public client bundles.

## Debug Check

```typescript
console.log(client.getDebugInfo());
// {
//   basePath: 'https://your-instance.example/api',
//   projectId: 'xxxxxxxx-...',
//   timeout: 30000,
//   hasApiKey: true,
//   hasProjectUserToken: false,
// }
```

## References

- Full options/types/API surface: [reference.md](reference.md)
- Copy-ready adapters and setup snippets: [examples.md](examples.md)
- User auth flow details: `elmapi-user-auth`
- Error handling: `elmapi-errors`
