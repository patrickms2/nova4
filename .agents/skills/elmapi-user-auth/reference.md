# User Auth Reference

## Auth Events

| Event | When |
|-------|------|
| `'signed_in'` | After `signUp()`, `signInWithPassword()`, or `signInWithSocial()` succeeds |
| `'token_refreshed'` | After `refreshSession()` succeeds (including auto-refresh) |
| `'signed_out'` | After `signOut()` or `signOutAll()` |
| `'auth_error'` | When an auth operation fails |

## Session and Event Types

```typescript
interface AuthSession {
  accessToken: string | undefined;
  refreshToken: string | undefined;
  expiresAt: string | undefined;
  refreshTokenExpiresAt: string | undefined;
  user: unknown;
}

type AuthStateChangeEvent =
  | 'signed_in'
  | 'token_refreshed'
  | 'signed_out'
  | 'auth_error';
```

## Method Summary

| Method | Auth Required | Description |
|--------|--------------|-------------|
| `signUp(payload)` | None | Register a new user |
| `signInWithPassword(payload)` | None | Log in with email/password |
| `signInWithSocial(payload)` | None | Log in with provider OIDC `id_token` (`provider`, `id_token`, optional `nonce`) |
| `refreshSession()` | Refresh token | Refresh access token |
| `signOut()` | User token | Sign out current session |
| `signOutAll()` | User token | Sign out all sessions |
| `me()` | User token | Get current user |
| `changePassword(payload)` | User token | Change password |
| `resendVerificationEmail(payload)` | None | Issue verification token; use `verification_token` from the response when present to drive your own delivery flow |
| `confirmVerificationEmail(payload)` | None | Confirm email with token from your app’s link |
| `listUserApiKeys()` | User token | List this project end user's `uak_*` API keys |
| `createUserApiKey(payload)` | User token | Create an end-user `uak_*` key |
| `revokeUserApiKey(keyId)` | User token | Revoke an end-user `uak_*` key |
| `onAuthStateChange(callback)` | — | Subscribe to auth events |
| `getSession()` | — | Get current session data |

## Auto Refresh Behavior

When `autoRefresh: true` (default), the SDK attempts `refreshSession()` on 401 responses and retries the original request.

Concurrent refresh calls are deduplicated.

## Not exposed by the project API

- **OAuth2/OIDC provider** for third parties (no generic `/oauth/authorize` server to build against Elmapi).
- **CMS staff user** or **project member** management for app identities — only **project end-user auth** (password, social `id_token`, verification, user API keys).

## Token Storage Notes

To persist tokens across reloads, provide `tokenStorage` through `projectUserAuth` during client setup. See `elmapi-sdk-setup` for adapter examples.
