# Error Handling Examples

## Catch All SDK Errors

```typescript
try {
  await client.content.list('posts');
} catch (error) {
  if (error instanceof ElmapiError) {
    console.error(`${error.name}: ${error.message}`);
    console.error(`Status: ${error.statusCode}, Code: ${error.code}`);
    console.error('Request:', error.requestInfo);
  }
}
```

## Catch Specific Error Types

```typescript
try {
  await client.content.get('posts', 'some-uuid');
} catch (error) {
  if (error instanceof NotFoundError) return null;
  if (error instanceof AuthenticationError) {
    redirectToLogin();
    return;
  }
  if (error instanceof ValidationError) {
    console.error('Validation errors:', error.details);
    return;
  }
  throw error;
}
```

## Validation Error Field Mapping

```typescript
try {
  await client.content.create('posts', { data: { title: '' } });
} catch (error) {
  if (error instanceof ValidationError) {
    const fieldErrors = error.details as Record<string, string[]>;
    for (const [field, messages] of Object.entries(fieldErrors)) {
      console.error(`${field}: ${messages.join(', ')}`);
    }
  }
}
```

## Network/Timeout + Rate Limit

```typescript
try {
  await client.content.list('posts');
} catch (error) {
  if (error instanceof TimeoutError) console.error(error.message);
  if (error instanceof NetworkError) showOfflineMessage();
  if (error instanceof RateLimitError) {
    await new Promise((r) => setTimeout(r, 2000));
    return client.content.list('posts');
  }
}
```
