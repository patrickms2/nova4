---
name: elmapi-errors
description: >-
  Handles Elmapi SDK errors — error class hierarchy, properties, and
  try/catch patterns. Use when handling API errors, debugging failed requests,
  or implementing error boundaries with Elmapi.
---

# Elmapi Error Handling

## Quick Start

```typescript
try {
  await client.content.list('posts');
} catch (error) {
  if (error instanceof ElmapiError) {
    console.error(error.name, error.message);
  }
}
```

## Specific Error Handling

```typescript
try {
  await client.content.get('posts', 'some-uuid');
} catch (error) {
  if (error instanceof NotFoundError) {
    return null;
  }
  if (error instanceof AuthenticationError) {
    redirectToLogin();
    return;
  }
  if (error instanceof ValidationError) {
    // 422 — invalid data
    console.error('Validation errors:', error.details);
    return;
  }
  throw error; // re-throw unexpected errors
}
```

## Validation Error Details

```typescript
try {
  await client.content.create('posts', {
    data: { title: '' },
  });
} catch (error) {
  if (error instanceof ValidationError) {
    // error.details contains field-level validation errors
    const fieldErrors = error.details as Record<string, string[]>;
    for (const [field, messages] of Object.entries(fieldErrors)) {
      console.error(`${field}: ${messages.join(', ')}`);
    }
  }
}
```

## Network and Timeout

```typescript
try {
  await client.content.list('posts');
} catch (error) {
  if (error instanceof TimeoutError) {
    console.error(error.message);
  }
  if (error instanceof NetworkError) {
    showOfflineMessage();
  }
}
```

## Rate Limit Retry

```typescript
try {
  await client.content.list('posts');
} catch (error) {
  if (error instanceof RateLimitError) {
    await new Promise(r => setTimeout(r, 2000));
    return client.content.list('posts');
  }
}
```

## References

- Full hierarchy, properties, and scenario table: [reference.md](reference.md)
- Copy-ready catch blocks: [examples.md](examples.md)
