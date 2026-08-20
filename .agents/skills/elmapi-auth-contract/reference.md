# Auth Contract Playbook

## Definition of done (framework agnostic)

- Login path creates authenticated app state from backend response.
- Refresh path updates stored tokens atomically after success.
- Refresh path signs out on invalid refresh token.
- Logout path revokes backend session and then clears local state.
- Session behavior is documented for developers.

## Failure-mode playbook

| Situation | Required behavior |
|---|---|
| Access token expired | Attempt one refresh, retry request once |
| Refresh token invalid/reused | Clear auth state and require fresh login |
| Logout network failure | Surface error and avoid false success message |
| Backend already revoked session | Treat as signed out but note revoke status |
| Social login token exchange fails | Preserve local logged-out state and show retry action |

## Documentation snippet (recommended)

"Elmapi creates one backend session per successful sign-in. A session remains active until revoked or expired. Logging out must call backend revoke; clearing local cookies alone does not end the backend session."
