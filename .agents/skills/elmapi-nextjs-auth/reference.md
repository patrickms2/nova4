# Next.js Auth Definition of Done

Use this checklist for new apps and rewrites.

## Must-pass acceptance checks

- Login stores backend-issued access + refresh token.
- Access token expiry triggers exactly one refresh attempt.
- Refresh success persists rotated tokens.
- Refresh failure signs user out and redirects to login.
- Logout calls backend revoke before local sign-out.
- Register flow does not create duplicate sessions.
- `401` handling is consistent between server routes and client pages.

## Manual test matrix

1. Login -> visit protected page -> `/api/me` returns 200.
2. Force access token expiry -> next protected call refreshes and succeeds.
3. Invalidate refresh token -> next protected call signs out.
4. Logout -> verify backend session revoked in Elmapi sessions page.
5. Register with immediate session enabled -> exactly one active new session.
6. Login -> logout -> login -> expected session count and statuses.

## Common failure modes and fixes

| Symptom | Root cause | Fix |
|---|---|---|
| Multiple active sessions after basic logout/login | Cookie-only logout or revoke not called | Call backend `signOut()` before local clear |
| Works once then auth breaks later | Refresh rotation not persisted | Update `refreshToken` and `expiresAt` in JWT callback |
| User appears logged in but `/api/me` is 401 | Stale local session after token failure | Force sign-out and redirect to login |
| Two sessions right after register | Register then extra credentials login | Skip second login when signup already created session |

## PR output requirements

- List changed auth files.
- Show where revoke is called.
- Show where refresh rotation is persisted.
- Include completed acceptance checklist results.
