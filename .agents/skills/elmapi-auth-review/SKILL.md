---
name: elmapi-auth-review
description: >-
  Audits an existing frontend auth implementation against Elmapi auth contract
  and returns concrete findings plus a patch plan.
---

# Elmapi Auth Integration Review

Use this when user asks to review an auth implementation or debug session behavior.

## Audit procedure

1. Locate login, refresh, and logout handlers.
2. Confirm backend revoke exists in logout path.
3. Confirm refresh retry strategy (single retry).
4. Inspect token storage and exposure risks.
5. Validate middleware/private route behavior.
6. Verify docs/UI text about session semantics.

## Findings severity

- Critical: no backend logout revoke, refresh token leakage, server key exposed
- High: broken refresh rotation, silent logout failure
- Medium: missing auth failure UX or weak guidance
- Low: naming/structure consistency improvements

## Required output format

- Findings list (severity, file, issue)
- Minimal patch plan
- Verification checklist
- Expected session behavior note

## Expected session behavior note

- Multiple active sessions can be normal.
- It becomes abnormal when revoke was expected but never called or failed.

## References

- `elmapi-auth-contract`
- `elmapi-nextjs-auth`
- `elmapi-user-auth`
- Next.js acceptance matrix: `elmapi-nextjs-auth/reference.md`
