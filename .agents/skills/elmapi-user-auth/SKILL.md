---
name: elmapi-user-auth
description: >-
  Implements Elmapi project end-user authentication — sign up, password sign-in,
  OIDC id_token (social) sign-in, token refresh, sign out, email verification, user API keys,
  and auth state management. Use when building login/register flows or managing end-user
  sessions. Not for CMS staff user administration or OAuth provider integration.
---

# Elmapi User Auth

Auth methods are called directly on the client (not namespaced). Configure the client with `projectUserAuth` — see `elmapi-sdk-setup` for client creation.

## Required integration semantics

- A successful sign-in creates a new backend session.
- `signOut()` must be called to revoke backend session.
- Clearing local cookies/storage without backend revoke is not a complete logout.
- Multiple active sessions may appear when previous sessions were not revoked.

For framework-agnostic guardrails and must-pass checks, read `elmapi-auth-contract` first.

## Identity-only + CMS writes (BFF)

Project Auth tokens identify the end user (`me()`). They are **not** a substitute for the project API token when writing CMS content that the dashboard attributes to staff/API.

Recommended pattern for user-generated content (comments, reviews, etc.):

1. Authenticate the user with Project Auth in the browser.
2. Call your app **BFF** (Route Handler / server API) with the user’s session.
3. BFF verifies identity via `me()` (or trusted session claims), then writes CMS content with the **server project API token**.
4. Persist author display fields on the entry (kebab-case), e.g. `author-name`, `author-user-id` — do **not** rely on CMS `created_by` for Project Auth users (dashboard User FK shows as **API**).


## Email verification

When verification is required, API responses include **`verification_token`** (for example on sign-up `202`, on blocked login/refresh `403`, or on `verify-email/resend`). Your application delivers the verification link or code (email, SMS, in-app, etc.) and calls **`confirmVerificationEmail({ token })`** when the user completes the flow (or your server wraps the same API).

## Quick Start

```typescript
await client.signUp({
  email: 'user@example.com',
  password: 'securePassword123',
  display_name: 'Jane Doe',
});
await client.signInWithPassword({
  email: 'user@example.com',
  password: 'securePassword123',
});
await client.signInWithSocial({
  provider: 'google',
  id_token: idTokenFromYourOAuthFlow,
});
await client.refreshSession();
await client.signOut();
```

`signUp()`, `signInWithPassword()`, and `signInWithSocial()` store tokens and emit `'signed_in'`. With `autoRefresh: true`, the SDK refreshes on 401 and retries automatically.

## Social sign-in (OIDC `id_token`)

1. Your app runs OAuth with the provider (e.g. Google) and obtains an OpenID Connect **`id_token`** (JWT).
2. Call `signInWithSocial({ provider: 'google', id_token, nonce? })`. The backend verifies the JWT (issuer, JWKS, audience, email) and returns the **same** access/refresh payload as password login.
3. Prefer calling this from a **server route** (Next.js Route Handler, Nuxt server API, etc.) so the raw `id_token` is not logged in the browser.

The provider OAuth **client ID** must be allowed on the Elmapi instance (`aud` in the token). Optional `nonce` when your OIDC flow used nonce and the token includes it.

## What is not part of this skill

- **No Elmapi OAuth2/OIDC provider** for integrators — you do not register “clients” with Elmapi to act as an OAuth server; you only **exchange** a provider `id_token` for a project user session.
- **No CMS user directory or project-member APIs** for managing staff or workspace members via the project API — use project user auth for **your app’s end users** only.

## Current User

```typescript
const user = await client.me();
```

## Common Operations

```typescript
await client.changePassword({
  current_password: 'oldPassword',
  new_password: 'newPassword123',
});
await client.resendVerificationEmail({ email: 'user@example.com' });
await client.confirmVerificationEmail({ token: 'verification-token' });
```

## User API Keys (end-user `uak_*` keys)

These methods call `/api/auth/api-keys` for the **signed-in project end user**. They are **not** for dashboard CMS tokens (Sanctum PATs).

```typescript
const keys = await client.listUserApiKeys();
const key = await client.createUserApiKey({
  name: 'My App Key',
  scopes: ['content:read', 'content:write'],
  expires_at: '2026-01-01T00:00:00Z',
});
await client.revokeUserApiKey(key.id);
```

## Auth State Changes

```typescript
const unsubscribe = client.onAuthStateChange((event, session) => {
  console.log(event, session);
});
unsubscribe();
```

## Get Current Session

```typescript
const session: AuthSession = client.getSession();
```

## References

- Full method matrix, events, and types: [reference.md](reference.md)
- Copy-ready auth flow snippets: [examples.md](examples.md)
- Token storage adapter setup: `elmapi-sdk-setup`
- Next.js patterns (including server forwarding): `elmapi-nextjs`
- Contract and integration checklist: `elmapi-auth-contract`
- Next.js auth guardrails and review flow: `elmapi-nextjs-auth`, `elmapi-auth-review`
