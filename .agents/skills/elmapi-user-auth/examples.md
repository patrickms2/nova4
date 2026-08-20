# User Auth Examples

## Sign Up + Sign In

```typescript
const signUpResult = await client.signUp({
  email: 'user@example.com',
  password: 'securePassword123',
  display_name: 'Jane Doe',
});

if (signUpResult.verification_required) {
  // Deliver verification using signUpResult.verification_token (your email/SMS/app surface).
  // Then user opens your link; call confirmVerificationEmail({ token }) with the same raw token.
}

await client.signInWithPassword({
  email: 'user@example.com',
  password: 'securePassword123',
});
```

## Social sign-in (after Google OAuth)

```typescript
// Typically from a server route: receive id_token from your OAuth callback handler,
// then exchange it for Elmapi session tokens.
await client.signInWithSocial({
  provider: 'google',
  id_token: rawIdTokenJwt,
  // nonce: 'matching-nonce', // if your authorize request used nonce
});
```

## Password + Verification

```typescript
await client.changePassword({
  current_password: 'oldPassword',
  new_password: 'newPassword123',
});

const resend = await client.resendVerificationEmail({ email: 'user@example.com' });
if (resend.verification_token) {
  // send verification message from your app
}
await client.confirmVerificationEmail({ token: 'verification-token' });
```

## Session Helpers

```typescript
const user = await client.me();
const session = client.getSession();
await client.refreshSession();
await client.signOutAll();
```

## Auth State Subscription

```typescript
const unsubscribe = client.onAuthStateChange((event, session) => {
  if (event === 'signed_out') {
    console.log('User signed out');
  }
  if (event === 'auth_error') {
    console.error('Auth flow failed', session);
  }
});

unsubscribe();
```

## User API Keys

```typescript
const keys = await client.listUserApiKeys();

const key = await client.createUserApiKey({
  name: 'My App Key',
  scopes: ['content:read'],
});

await client.revokeUserApiKey(key.id);
```
