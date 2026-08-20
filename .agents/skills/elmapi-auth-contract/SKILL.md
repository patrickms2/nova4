---
name: elmapi-auth-contract
description: >-
  Canonical contract for integrating Elmapi project user auth in any frontend.
  Enforces correct login, refresh, logout, and session semantics. Use whenever
  implementing or reviewing auth flows (custom, NextAuth, Nuxt, Astro, mobile).
---

# Elmapi Auth Contract (Required)

This is the source of truth for all frontend integrations.

## Required session semantics

- Each successful sign-in creates a new backend session.
- Logout is complete only after backend session revoke succeeds.
- Clearing local cookies/storage alone is not logout.
- Multiple sessions can be valid unless revoked/expired/max-session-limited.

## Required endpoints/SDK calls

- Sign in: `signInWithPassword()` or `signInWithSocial()`
- Refresh: `refreshSession()` (refresh token rotation)
- Logout current session: `signOut()` (backend revoke)
- Logout all sessions (optional security action): `logoutAllSessions()` or API equivalent
- Current user: `me()`

## Integration checklist (must pass)

1. Login stores access + refresh tokens from backend.
2. API client sends bearer access token.
3. On access token expiry, refresh is attempted once.
4. Refresh success updates stored tokens atomically.
5. Refresh failure clears local auth state and routes to sign-in.
6. Logout calls backend revoke endpoint before local state clear.
7. If logout fails, UI reports failure; do not silently fake logout.
8. Team-facing docs explain why multiple sessions may appear.

## Error handling rules

- `401` on protected API: attempt refresh once, then fail-auth.
- Invalid refresh token: hard sign-out and require re-login.
- Network error during logout: do not report success before revoke confirmation.

## Email verification

- The API issues **`verification_token`**; your application owns delivery (email, SMS, in-app) and confirmation UX.
- Document where tokens appear (sign-up, blocked auth, resend) so implementers wire delivery correctly.

## Security boundaries

- Never expose server project API tokens in browser code.
- Never log raw refresh tokens or social `id_token` values.
- Prefer server routes for social `id_token` exchange when social login is enabled on the instance.
- Follow framework best practices for secure token storage.
- Project Auth is **identity-only** for CMS authorship: user-generated CMS writes go through a BFF using the project API token; store author fields on the content entry.

## Output expectations for AI agents

When asked to implement auth, include:

- Login/refresh/logout flow summary.
- Token storage approach and rationale.
- Explicit location of backend logout revoke call.
- Failure paths for refresh/logout.
- Test plan including logout -> login -> sessions verification.

## References

- Contract playbook and DoD: [reference.md](reference.md)
- `elmapi-user-auth`
- `elmapi-nextjs-auth`
- `elmapi-nuxt`
- `elmapi-errors`
