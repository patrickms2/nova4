# Server External Sync Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add server-scoped local synchronization tables, normalization services, and Filament resources so Nova MCP can normalize responses from external MCPs (WooCommerce, LatePoint, Magento, Sirvo) and register them in local tables with visible source attribution.

**Architecture:** Nova MCP acts as the central normalizer. External MCPs only execute actions (create_booking, create_order). Nova MCP:
1. Receives response from external MCP
2. Normalizes to Nova structure
3. Registers in `nova_external_bookings/orders/transactions`
4. External MCPs don't need to know Nova's structure

**Tech Stack:** Laravel 12, Eloquent, Filament 5.1, PHPUnit, Laravel HTTP/database fakes.

---

### Task 1: Normalization Services

**Files:**
- Create: `app/Services/Nova/NovaResponseNormalizer.php`
- Modify: `app/Services/Nova/NovaOrchestratorService.php`
- Test: `tests/Feature/NovaResponseNormalizerTest.php`

- [ ] **Step 1: Write failing tests**

Create `tests/Feature/NovaResponseNormalizerTest.php` with tests that:
- Normalize Sirvo booking response to Nova structure
- Normalize LatePoint booking response to Nova structure
- Normalize Magento order response to Nova structure
- Normalize WooCommerce order response to Nova structure

- [ ] **Step 2: Run red test**

Run: `php artisan test tests/Feature/NovaResponseNormalizerTest.php`

Expected: FAIL because normalizer doesn't exist.

- [ ] **Step 3: Implement normalizer**

Create `NovaResponseNormalizer` with methods:
- `normalizeBookingResponse(array $response, Server $server): array`
- `normalizeOrderResponse(array $response, Server $server): array`
- `normalizeTransactionResponse(array $response, Server $server): array`

- [ ] **Step 4: Integrate in NovaOrchestratorService**

Add normalizer calls after MCP tool execution:
```php
$result = $this->mcpClient->executeTool(...);
$normalized = $this->normalizer->normalizeBookingResponse($result, $server);
$this->registerBooking($normalized, $server, $intent);
```

- [ ] **Step 5: Run green test**

Run: `php artisan test tests/Feature/NovaResponseNormalizerTest.php`

Expected: PASS.

### Task 2: Registration in Nova Tables

**Files:**
- Modify: `app/Services/Nova/NovaOrchestratorService.php`
- Create: `app/Services/Nova/NovaRegistrationService.php`
- Test: `tests/Feature/NovaRegistrationServiceTest.php`

- [ ] **Step 1: Write failing tests**

Create `tests/Feature/NovaRegistrationServiceTest.php` with tests that:
- Register normalized booking in `nova_external_bookings`
- Register normalized order in `nova_external_orders`
- Register normalized transaction in `nova_external_transactions`
- Preserve source attribution (server name, external_id)

- [ ] **Step 2: Run red test**

Run: `php artisan test tests/Feature/NovaRegistrationServiceTest.php`

Expected: FAIL because registration service doesn't exist.

- [ ] **Step 3: Implement registration service**

Create `NovaRegistrationService` with methods:
- `registerBooking(array $normalized, Server $server, string $intent): NovaExternalBooking`
- `registerOrder(array $normalized, Server $server): NovaExternalOrder`
- `registerTransaction(array $normalized, Server $server): NovaExternalTransaction`

- [ ] **Step 4: Integrate in NovaOrchestratorService**

Add registration calls after normalization:
```php
$normalized = $this->normalizer->normalizeBookingResponse($result, $server);
$booking = $this->registrationService->registerBooking($normalized, $server, $intent);
```

- [ ] **Step 5: Run green test**

Run: `php artisan test tests/Feature/NovaRegistrationServiceTest.php`

Expected: PASS.

### Task 3: Filament Resources for Nova External Tables

**Files:**
- Create: `app/Filament/Resources/NovaExternalBookingResource.php`
- Create: `app/Filament/Resources/NovaExternalOrderResource.php`
- Create: `app/Filament/Resources/NovaExternalTransactionResource.php`
- Test: `tests/Feature/NovaExternalFilamentResourcesTest.php`

- [ ] **Step 1: Write failing tests**

Test that each resource targets the correct model and shows source attribution (server name, external_id).

- [ ] **Step 2: Run red test**

Run: `php artisan test tests/Feature/NovaExternalFilamentResourcesTest.php`

Expected: FAIL because the resources do not exist.

- [ ] **Step 3: Implement resources**

Add table columns, filters, and read-oriented navigation under Nova section.

- [ ] **Step 4: Run green test**

Run: `php artisan test tests/Feature/NovaExternalFilamentResourcesTest.php`

Expected: PASS.

---

## Summary

With this architecture:
- External MCPs (WooCommerce, LatePoint, Magento, Sirvo) only execute actions
- Nova MCP normalizes all responses to a common structure
- Nova MCP registers all bookings/orders/transactions in local tables
- External MCPs don't need to know Nova's structure
- New external services can be integrated in minutes by adding a server record and mapping
