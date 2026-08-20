# Error Handling Reference

## Imports

```typescript
import {
  ElmapiError,
  AuthenticationError,
  AuthorizationError,
  NotFoundError,
  ValidationError,
  RateLimitError,
  ServerError,
  NetworkError,
  TimeoutError,
} from '@elmapicms/js-sdk';
```

## Error Hierarchy

```
Error (built-in)
└── ElmapiError
    ├── AuthenticationError  (401, AUTHENTICATION_ERROR)
    ├── AuthorizationError   (403, AUTHORIZATION_ERROR)
    ├── NotFoundError        (404, NOT_FOUND)
    ├── ValidationError      (422, VALIDATION_ERROR)
    ├── RateLimitError       (429, RATE_LIMIT_ERROR)
    ├── ServerError          (500, SERVER_ERROR)
    ├── NetworkError         (no statusCode, NETWORK_ERROR)
    └── TimeoutError         (no statusCode, TIMEOUT_ERROR)
```

## `ElmapiError` Properties

```typescript
class ElmapiError extends Error {
  readonly name: string;
  readonly statusCode: number | undefined;
  readonly code: string | undefined;
  readonly details: unknown;
  readonly requestInfo: {
    method: string;
    url: string;
    params?: unknown;
    data?: unknown;
  } | undefined;
}
```

## Common Scenarios

| Error Class | When It Occurs |
|------------|----------------|
| `AuthenticationError` | Missing/expired/invalid token |
| `AuthorizationError` | Valid token but insufficient permissions |
| `NotFoundError` | Collection slug or entry UUID doesn't exist |
| `ValidationError` | Invalid payload data (create/update/patch) |
| `RateLimitError` | Too many requests in a short time |
| `ServerError` | Elmapi server-side error (5xx) |
| `NetworkError` | No network, DNS failure, connection refused |
| `TimeoutError` | Request exceeded `timeout` (default 30s) |

## `instanceof` Guarantees

Error classes are proper ES classes with correct prototype chains:

```typescript
error instanceof ValidationError // true for 422
error instanceof ElmapiError     // true for all SDK errors
error instanceof Error           // true
```
