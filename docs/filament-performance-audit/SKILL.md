w---
name: filament-performance-audit
description: Audit Filament PHP applications for performance issues in resources, tables, forms, widgets, navigation badges, global search, Livewire behavior, Eloquent queries, caching, and deployment readiness. Use for Filament performance reviews, slow admin panels, large datasets, N+1 problems, expensive closures, polling, and concrete Filament performance checks.
---

# Filament Performance Audit

Use this skill when reviewing, diagnosing, or improving performance in a Filament application. It is an audit skill: identify concrete performance risks, explain impact, and recommend targeted fixes. Do not turn it into a broad style or deprecation review unless the pattern affects performance.

## Required Context

1. Read the repository's agent or project guidance files for declared package versions and project rules.
2. Use Laravel Boost `application_info` to confirm installed versions when available.
3. Use Laravel Boost `search_docs` before recommending version-sensitive Filament, Laravel, or Livewire APIs.
4. Activate and use `laravel-best-practices` whenever the audit touches Eloquent queries, database indexes, caching, queues, HTTP calls, Blade, configuration, or deployment performance.
5. Use `database_schema` before making index, search, sort, relationship, or query-shape recommendations.

## Audit Workflow

1. Locate Filament resources, relation managers, pages, widgets, panel providers, and related models.
2. Review the code path the user asked about first; expand only to directly related files.
3. Route to the references below based on what is present.
4. Report findings first, ordered by severity, with file and line references.
5. Mark findings as verified when visible in code/schema; mark them as inferred when they depend on runtime data size, traffic, or production configuration.
6. Avoid speculative advice. Every recommendation should connect to observed code, schema, package behavior, or a known Filament pattern.

## Reference Routing

- Tables, resources, relation managers, columns, filters, searches, exports: read `references/tables.md`.
- Forms, selects, repeaters, uploads, reactive fields, large form schemas: read `references/forms.md`.
- Widgets, stats, dashboard panels, navigation badges, panel boot paths: read `references/widgets-navigation.md`.
- Global search, searchable resources, search result rendering: read `references/global-search.md`.
- Concrete Filament performance checks with bad and good examples: read `references/concrete-checks.md`.
- Final report format and severity guidance: read `references/audit-output.md`.

## Verification

Prefer the minimum useful verification:

- Run targeted Pest tests if the project already has tests for the audited behavior.
- Use query-count checks, database logs, Telescope, Debugbar, or Laravel query listeners when the user asks for runtime verification.
- Do not create one-off verification scripts when existing tests or tooling can prove the behavior.
