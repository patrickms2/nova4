# Filament Admin Security Audit

Date: 2026-06-14
Scope: Filament admin panel, discovered Filament resources/pages/widgets, admin plugins, panel access gates, and related admin-facing model authorization.

## Context Used

- `docs/01-project-vision.md`
- `docs/02-technical-spec.md`
- `docs/03-ui-system.md`
- `docs/04-engineering-rules.md`
- Laravel Brain scan/export generated during this audit

Note: the requested `filament-security-audit` skill was not available in the configured tool list, so this audit used the closest available Laravel security/codebase audit workflow plus manual Filament verification.

## Executive Summary

The admin panel currently relies heavily on coarse authenticated access and ad hoc resource-level checks. Several resources and model methods explicitly return `true` before role/section checks, which effectively bypasses intended authorization for any authenticated Filament user. The most important remediation is to centralize panel access, replace unconditional `return true` gates, add policies or resource authorization helpers, and lock down high-risk plugins such as database browsing and impersonation.

Laravel Brain scan summary:

- Nodes: 3,805
- Edges: 6,212
- Routes: 425
- Filament resources: 59
- Security issues flagged: 122
- Viewer: `https://novahubmcp.test/_laravel-brain`

Security category breakdown:

| Count | Type |
|---:|---|
| 110 | `PUBLIC_WRITE` |
| 12 | `MISSING_THROTTLE` |
| 1 | `XSS_BLADE_UNESCAPED` |

Interpretation: the scan is dominated by public write/middleware findings. For Filament, the more immediate verified risk is not lack of authentication middleware on `/admin` itself, but overly broad authorization once a user is authenticated.

## Verified Findings

### P0 — Panel Access Allows Every Authenticated `User`

**File:** `app/Models/User.php:87`

```php
public function canAccessPanel(Panel $panel): bool
{
    return true;
}
```

**Risk:** Any authenticated `User` model can access every Filament panel that uses this user class, regardless of role, section, email verification, status, tenancy, or panel ID.

**Remediation:** Replace with explicit panel-aware checks, for example:

- `admin` panel: `super_admin`, selected admin roles only.
- `knowledge-base` panel: explicitly authorized knowledge/Nova admins only.
- reject inactive/unverified users.
- deny by default for unknown panels.

### P0 — Tenant Access Allows Every Tenant

**File:** `app/Models/User.php:92`

```php
public function canAccessTenant(Model $tenant): bool
{
    return true;
}
```

**Risk:** If Filament tenancy is enabled or introduced, users can access any tenant record. This is a cross-tenant data exposure risk.

**Remediation:** Implement tenant ownership/membership checks and deny by default. Add tests proving users cannot access unrelated tenants/businesses.

### P0 — Taxi User Model Grants Superadmin and Impersonation to Everyone

**File:** `app/Models/Taxi/Usuario.php:73-86`

```php
public function canImpersonate()
{
    return true;
}

public function canBeImpersonated()
{
    return true;
}

public function isSuperAdmin(): bool
{
    return true;
}
```

**Risk:** Any user represented by this model can impersonate others, can be impersonated, and is treated as superadmin. Combined with admin table actions, this is a critical privilege-escalation path.

**Remediation:** Restrict impersonation to audited superadmins only; prevent impersonating superadmins/admins; log impersonation start/stop; require confirmation; consider disabling impersonation until authorization is fixed.

### P0 — Database Browser Plugin Is Authorized for Everyone

**File:** `app/Providers/Filament/AdminPanelProvider.php:224-229`

```php
FilamentDatabasePlugin::make()
    ->authorize(function() {
        return true;
    }),
```

**Risk:** Any authenticated admin-panel user can potentially inspect database structure/data depending on plugin capabilities. This can expose PII, booking data, payment metadata, tokens, configuration, and operational records.

**Remediation:** Restrict to `super_admin` only, or disable outside local development. Add environment gate: only local/staging plus explicit role. Audit plugin capabilities and routes.

### P0 — Multiple Filament Resources Bypass Intended Role Checks With Early `return true`

Verified examples:

- `app/Filament/Resources/RestaurantAdmin/Resources/RestaurantBookingResource.php:32-45`
- `app/Filament/Resources/TourAdmin/Resources/TourCategoryResource.php:28-43`
- `app/Filament/Resources/TourAdmin/Resources/ActivityResource.php:28-43`
- `app/Filament/Resources/TourAdmin/Resources/TourBookingResource.php:30-37`
- `app/Filament/Resources/TourBookingResource.php:30-35`
- `app/Filament/Resources/TourSubAdmin/Resources/PackageBookingResource.php:36-42`
- `app/Filament/Resources/TourSubAdmin/Resources/TourTranslationResource.php:30-42`
- `app/Filament/Resources/TourSubAdmin/Resources/TourImageResource.php:32-44`
- `app/Filament/Resources/TourSubAdmin/Resources/TourScheduleResource.php:31-54`
- `app/Filament/Resources/TravelAdmin/Resources/TravelAgencyResource.php:26-38`
- `app/Filament/Resources/TravelSubAdmin/Resources/TravelPackageResource.php:26-38`
- `app/Filament/Resources/TravelSubAdmin/Resources/PackageInclusionResource.php:25-37`
- `app/Filament/Resources/TravelSubAdmin/Resources/PackageDestinationResource.php:26-38`

**Risk:** Navigation and direct URL access can be available to any authenticated Filament user, bypassing role/section restrictions that appear below unreachable `return true` statements.

**Remediation:** Remove early returns and centralize checks in a shared authorization helper/trait. Add feature tests for direct access to list/create/edit/view pages for each role.

### P1 — Travel SubAdmin Resources Are Not Tenant-Scoped Correctly

Verified examples:

- `TravelPackageResource` sets `agency_id` to `1` (`app/Filament/Resources/TravelSubAdmin/Resources/TravelPackageResource.php:48-49`).
- `PackageInclusionResource` uses `$agency_id = 1` and `TravelPackage::all()` (`app/Filament/Resources/TravelSubAdmin/Resources/PackageInclusionResource.php:49-52`).
- `PackageDestinationResource` has intended agency filtering in the package select, but `canAccess()` is unconditional and no `getEloquentQuery()` tenant scope is enforced.

**Risk:** Subadmins can view, create, or modify records for the wrong agency. Hardcoded IDs can attach data to another business.

**Remediation:** Resolve the current user's agency dynamically; apply `getEloquentQuery()` scopes; use relationship options scoped to the current agency; enforce ownership in mutate hooks and policies.

### P1 — Bulk Deletes Are Widely Enabled Without Verified Authorization

Examples found across Filament resources:

- `DriverResource`
- `NovaBusinessesTable`
- `NovaCrossSellingRulesTable`
- `NovaListingCategoriesTable`
- `PagoResource` / payment tables
- `Package*` resources
- `Usuarios` relation managers

**Risk:** If access gates are broad, bulk delete actions allow destructive cross-domain changes. High-risk tables include businesses, payments, users, bookings, knowledge, and integration records.

**Remediation:** Disable bulk delete by default. Re-enable only per resource after policy checks, ownership constraints, and audit logging are in place. Require confirmation copy for destructive actions.

### P1 — Admin Spotlight Exposes Sensitive/Unreviewed Actions

**File:** `app/Providers/Filament/AdminPanelProvider.php:186-213`

Findings:

- Duplicate `impersonate` Spotlight actions point to `/impersonate`.
- Public links such as `/ai-bot` and `/explore` are mixed into admin global search.
- MCP/business dashboards are exposed via global search.

**Risk:** Search actions can bypass expected navigation hiding and make sensitive actions discoverable to any panel user.

**Remediation:** Gate each Spotlight action with explicit role checks. Remove duplicate impersonate action. Hide dev/public tools from production admin search unless explicitly approved.

### P1 — No `app/Policies` Directory Present

**Observation:** `app/Policies` does not exist.

**Risk:** The app appears to rely on resource-level checks and model methods instead of consistent model policies. With many early `return true` gates, there is no second authorization layer for direct page/action access.

**Remediation:** Add policies for core admin models: `NovaBusiness`, `NovaMcpServer`, `NovaIntegrationSetting`, `NovaRequest`, `PublicBookingRequest`, `Tour`, `Restaurant`, `Hotel`, `TravelAgency`, `TravelPackage`, `TaxiBooking`, payments, and user/taxista models.

### P2 — Scanner Flags 110 Public Write Routes and 12 Missing Throttles

**Source:** Laravel Brain security scan.

**Risk:** Some may be false positives due to middleware grouping, but the count is high enough to require route-level verification. Public booking/payment/webhook endpoints should be explicitly throttled and signed/authenticated where applicable.

**Remediation:** Export route list by prefix and verify middleware for:

- `/api/*` write endpoints
- webhooks
- payment callbacks
- `/explore` public booking/package endpoints
- admin-adjacent utility routes such as `/impersonate`

Add `throttle`, signature validation, auth, or webhook secret checks as appropriate.

## Remediation Plan

### Phase 1 — Emergency Access Lockdown

1. Replace `User::canAccessPanel()` with panel-aware role/status checks.
2. Replace `User::canAccessTenant()` with real tenant membership checks or deny by default.
3. Disable or superadmin-gate `FilamentDatabasePlugin`.
4. Disable impersonation globally until `Usuario::canImpersonate()`, `canBeImpersonated()`, and `isSuperAdmin()` are fixed.
5. Remove all early `return true` statements from Filament `canAccess()` and `shouldRegisterNavigation()` methods.

Acceptance criteria:

- Non-admin users cannot access `/admin`.
- Restaurant admins cannot access tour/travel resources by direct URL.
- Tour/travel subadmins cannot access records outside their agency/section.
- Database plugin is inaccessible to non-superadmins.

### Phase 2 — Centralize Authorization

1. Create a shared Filament authorization helper or trait, e.g. `AuthorizesFilamentSections`.
2. Define canonical roles/sections and normalize inconsistent names such as `super_admin`, `superadmin`, `Admin`, `UserType`, `role`, and `role_name`.
3. Add policies for core admin models.
4. Update resources to use policies and scoped `getEloquentQuery()` instead of duplicated ad hoc checks.
5. Gate global search/Spotlight actions with the same helper.

Acceptance criteria:

- Authorization logic is not duplicated across resources.
- Every sensitive resource has policy-backed view/create/update/delete checks.
- Direct URL access and navigation visibility produce the same authorization result.

### Phase 3 — Tenant and Ownership Scoping

1. Scope Travel resources by the current user's `TravelAgency`.
2. Remove hardcoded `agency_id = 1` values.
3. Scope Tour subadmin resources to the current user's tour(s).
4. Scope Restaurant resources to the current user's restaurant(s).
5. Scope NovaBusiness child resources to the current record/business.

Acceptance criteria:

- Form select options only show records owned by the current tenant/business.
- Query scopes prevent cross-tenant records from appearing in tables.
- Create/update hooks reject mismatched tenant IDs even if submitted manually.

### Phase 4 — Destructive Action Hardening

1. Remove broad `DeleteBulkAction` from sensitive resources.
2. Require policies for every delete action.
3. Add audit logs for delete, impersonation, integration setting changes, and payment changes.
4. Add soft deletes where operational records should not be physically deleted.
5. Require confirmation text for destructive actions on businesses, users, payments, bookings, integrations, MCP servers, and knowledge bases.

Acceptance criteria:

- No bulk delete exists on sensitive resources unless explicitly approved.
- Delete actions are blocked by policy for unauthorized roles.
- Audit trail exists for privileged mutations.

### Phase 5 — Route and Public Write Review

1. Run `php artisan route:list --columns=method,uri,name,middleware` and classify all POST/PUT/PATCH/DELETE routes.
2. Verify middleware on public write endpoints.
3. Add throttling to auth-like, webhook, AI/chat, booking, and payment endpoints.
4. Require signed requests or shared secrets for webhooks.
5. Ensure payment callbacks are idempotent and signature-verified.

Acceptance criteria:

- No public write route is unclassified.
- Every public write route has either auth, signature verification, webhook secret validation, or a documented public-safe reason plus throttle.
- Laravel Brain `MISSING_THROTTLE` findings are resolved or documented.

### Phase 6 — Test Coverage

Add feature tests for each role:

- guest
- normal authenticated user
- restaurant admin
- restaurant subadmin
- tour admin
- tour subadmin
- travel admin
- travel subadmin
- super admin

Minimum tests:

- cannot access wrong panel/resource by direct URL
- cannot see wrong navigation items
- cannot create/update/delete records outside ownership scope
- cannot use database plugin unless superadmin
- cannot impersonate unless superadmin
- bulk delete unavailable or unauthorized for non-superadmins

## Immediate Fix Checklist

- [ ] Fix `app/Models/User.php::canAccessPanel()`.
- [ ] Fix `app/Models/User.php::canAccessTenant()`.
- [ ] Fix `app/Models/Taxi/Usuario.php` impersonation/superadmin methods.
- [ ] Restrict or disable `FilamentDatabasePlugin` in `AdminPanelProvider`.
- [ ] Remove early `return true` gates from Tour, Restaurant, Travel resources.
- [ ] Replace hardcoded Travel agency IDs with current-user agency resolution.
- [ ] Add policies for high-risk models.
- [ ] Disable broad bulk deletes until policies are complete.
- [ ] Gate Spotlight actions, especially `/impersonate` and MCP/admin dashboards.
- [ ] Review all public write routes and add throttles/signatures.

## Recommended First Pull Request

Title: `Lock down Filament panel and resource authorization`

Scope:

1. Implement panel-aware `User::canAccessPanel()`.
2. Restrict `FilamentDatabasePlugin` to superadmins or local environment.
3. Disable impersonation methods that currently return true.
4. Remove early `return true` from the highest-risk resources.
5. Add focused feature tests for direct URL denial on wrong roles.

This PR should avoid UI redesign and focus only on authorization correctness.
