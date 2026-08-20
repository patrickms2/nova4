---
name: filament-blueprint
description: "Use before creating Filament proposals, blueprints, implementation plans, or new Filament Resources, Pages, Tables, Forms, Actions, Infolists, Widgets, Imports, Exports, Wizards, RelationManagers, or authorization flows. Requires reading the installed filament/blueprint planning guidelines first."
---

# Filament Blueprint Skill

Use this skill whenever the task involves creating a new Filament proposal, blueprint, plan, or implementation.

## Required Opening

At the start of the response or work update, state:

`Using Filament Blueprint`

Then read the installed Blueprint guidance before planning or editing code:

1. `vendor/filament/blueprint/resources/boost/guidelines/core.blade.php`
2. `vendor/filament/blueprint/resources/markdown/planning/overview.md`

Read only the additional Blueprint planning files that match the task:

- `resources.md` for Resources
- `forms.md` and `schema-layouts.md` for Forms and schema layout
- `tables.md` for table columns, filters, searches, sorting, and row actions
- `actions.md` and `bulk-actions.md` for page, header, table, row, and bulk actions
- `relationships.md` and `pivot-tables.md` for RelationManagers and relationship UI
- `reactive-fields.md` for dependent fields, Livewire state, calculations, and conditional behavior
- `custom-pages.md` for custom Filament pages
- `widgets.md` for dashboards and widgets
- `infolists.md` for read-only detail views
- `imports.md` and `exports.md` for data import/export flows
- `wizards.md` for multi-step flows
- `authorization.md` for policies, permissions, and visibility gates
- `testing.md` for required tests
- `styling.md` for visual customization

## Planning Rules

- Produce a specific implementation blueprint before writing code when the user asks for a proposal, a new Filament feature, or a non-trivial admin workflow.
- Make the blueprint concrete enough that another agent can implement without inventing behavior.
- Include model/table assumptions, relationships, Resource/Page placement, form structure, table columns, filters, actions, authorization, validation, tests, and open questions.
- Prefer the existing Nova panel/domain structure before adding new panels, clusters, folders, or abstractions.
- If the user asks to implement directly and the work is small, read the relevant Blueprint files and keep the plan brief, then implement.
- If information is missing and affects schema, workflow, authorization, or data ownership, ask before planning.

## Coordination With Other Project Rules

- Always also apply `docs/04-engineering-rules.md`.
- For Filament forms, resources, pages, tables, widgets, or input flows, also apply `docs/filament-forms-ux-audit/SKILL.md` after the Blueprint read.
- For Laravel backend changes, follow the local Laravel best-practice conventions and existing app patterns.
- Use tests for new behavior and run the focused verification commands before claiming completion.

## Completion Checklist

Before finishing a Filament Blueprint task, verify:

- Blueprint guidance files were read and named in the work update or final answer.
- The plan or implementation identifies the target panel/resource namespace.
- Forms and tables follow the UX audit where applicable.
- Authorization and validation are explicit.
- Tests or verification commands are listed with results.
