<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.4. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Follow existing application Enum naming conventions.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app/Console/Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.

- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== volt/core rules ===

# Livewire Volt

- Single-file Livewire components: PHP logic and Blade templates in one file.
- Always check existing Volt components to determine functional vs class-based style.
- IMPORTANT: Always use `search-docs` tool for version-specific Volt documentation and updated code examples.
- IMPORTANT: Activate `volt-development` every time you're working with a Volt or single-file component-related task.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

=== anousss007/blatui/core rules ===

## BlatUI

BlatUI is shadcn/ui for the Laravel BLAT stack (Blade, Alpine.js, Tailwind CSS v4).
Components are copy-paste Blade components the user **owns** — copied into
`resources/views/components/ui/`, not a runtime dependency. Works with or without Livewire.

### Using components

Components live under the `x-ui.` namespace. Use them directly in Blade and prefer
them over hand-rolled markup:

<code-snippet name="BlatUI components in Blade" lang="blade">
<x-ui.button variant="outline" size="sm">Save</x-ui.button>

<x-ui.dialog>
    <x-ui.dialog-trigger>
        <x-ui.button>Open</x-ui.button>
    </x-ui.dialog-trigger>
    <x-ui.dialog-content>
        <x-ui.dialog-header>
            <x-ui.dialog-title>Title</x-ui.dialog-title>
        </x-ui.dialog-header>
    </x-ui.dialog-content>
</x-ui.dialog>
</code-snippet>

Component APIs mirror shadcn/ui. `button` variants: `default`, `secondary`, `outline`,
`ghost`, `destructive`, `link`; sizes `sm`, `default`, `lg`, `icon`.

### Installing components

A component is only usable if its file exists in `resources/views/components/ui/`.
If it is missing, add it with the CLI — this copies the Blade source and prints the
required composer/npm peer packages. Never re-implement a component by hand if BlatUI
ships it.

<code-snippet name="Add BlatUI components" lang="shell">
php artisan blatui:add button card dialog   # copies files + their dependencies

php artisan blatui:list                      # browse everything available

php artisan blatui:init                      # verify theme tokens, Alpine, imports

</code-snippet>

### Theming

Every design token is a CSS variable on `:root` / `.dark` / `[data-*]` in
`resources/css/blatui.css`. Recolor by editing tokens, or design a theme visually at
https://blatui.remix-it.com/themes and paste the exported CSS into `resources/css/app.css`.

### Machine-readable registry

To read a component's exact source or discover what exists, use the registry
(shadcn-compatible, every file inlined):

- Index: `https://blatui.remix-it.com/registry.json`
- One item: `https://blatui.remix-it.com/r/<name>.json`
  (blocks: `/r/blocks/<name>.json`, charts: `/r/charts/<name>.json`)
- LLM index: `https://blatui.remix-it.com/llms.txt`

A hosted MCP server is available at `https://blatui.remix-it.com/mcp`, and a local one
via `php artisan blatui:mcp` (tools: search_registry, get_component, get_example,
install_command; resources `blatui://component|block|chart/{name}`).

=== filament/filament/core rules ===

## Filament

- Filament is a Laravel UI framework built on Livewire, Alpine.js, and Tailwind CSS. UIs are defined in PHP via fluent, chainable components. Follow existing conventions in this app.
- Use the `search-docs` tool for official documentation on Artisan commands, code examples, testing, relationships, and idiomatic practices. If `search-docs` is unavailable, refer to https://filamentphp.com/docs.

### Artisan

- Always use Filament-specific Artisan commands to create files. Find available commands with the `list-artisan-commands` tool, or run `php artisan --help`.
- Inspect required options before running, and always pass `--no-interaction`.

### Patterns

Always use static `make()` methods to initialize components. Most configuration methods accept a `Closure` for dynamic values.

Use `Get $get` to read other form field values for conditional logic:

<code-snippet name="Conditional form field visibility" lang="php">
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

Select::make('type')
    ->options(CompanyType::class)
    ->required()
    ->live(),

TextInput::make('company_name')
    ->required()
    ->visible(fn (Get $get): bool => $get('type') === 'business'),

</code-snippet>

Use `Set $set` inside `->afterStateUpdated()` on a `->live()` field to mutate another field reactively. Prefer `->live(onBlur: true)` on text inputs to avoid per-keystroke updates:

<code-snippet name="Reactive field update" lang="php">
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

TextInput::make('title')
    ->required()
    ->live(onBlur: true)
    ->afterStateUpdated(fn (Set $set, ?string $state) => $set(
        'slug',
        Str::slug($state ?? ''),
    )),

TextInput::make('slug')
    ->required(),

</code-snippet>

Compose layout by nesting `Section` and `Grid`. Children need explicit `->columnSpan()` or `->columnSpanFull()`:

<code-snippet name="Section and Grid layout" lang="php">
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

Section::make('Details')
    ->schema([
        Grid::make(2)->schema([
            TextInput::make('first_name')
                ->columnSpan(1),
            TextInput::make('last_name')
                ->columnSpan(1),
            TextInput::make('bio')
                ->columnSpanFull(),
        ]),
    ]),

</code-snippet>

Use `Repeater` for inline `HasMany` management. `->relationship()` with no args binds to the relationship matching the field name:

<code-snippet name="Repeater for HasMany" lang="php">
use Filament\Forms\Components\Repeater;

Repeater::make('qualifications')
    ->relationship()
    ->schema([
        TextInput::make('institution')
            ->required(),
        TextInput::make('qualification')
            ->required(),
    ])
    ->columns(2),

</code-snippet>

Use `state()` with a `Closure` to compute derived column values:

<code-snippet name="Computed table column value" lang="php">
use Filament\Tables\Columns\TextColumn;

TextColumn::make('full_name')
    ->state(fn (User $record): string => "{$record->first_name} {$record->last_name}"),

</code-snippet>

Use `SelectFilter` for enum or relationship filters, and `Filter` with a `->query()` closure for custom logic:

<code-snippet name="Table filters" lang="php">
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

SelectFilter::make('status')
    ->options(UserStatus::class),

SelectFilter::make('author')
    ->relationship('author', 'name'),

Filter::make('verified')
    ->query(fn (Builder $query) => $query->whereNotNull('email_verified_at')),

</code-snippet>

Actions are buttons that encapsulate optional modal forms and behavior:

<code-snippet name="Action with modal form" lang="php">
use Filament\Actions\Action;

Action::make('updateEmail')
    ->schema([
        TextInput::make('email')
            ->email()
            ->required(),
    ])
    ->action(fn (array $data, User $record) => $record->update($data)),

</code-snippet>

### Testing

Testing setup (requires `pestphp/pest-plugin-livewire` in `composer.json`):

- Always call `$this->actingAs(User::factory()->create())` before testing panel functionality.
- For edit pages, pass `['record' => $user->id]`, use `->call('save')` (not `->call('create')`), and do not assert `->assertRedirect()` (edit pages do not redirect after save).

<code-snippet name="Table test" lang="php">
use function Pest\Livewire\livewire;

livewire(ListUsers::class)
    ->assertCanSeeTableRecords($users)
    ->searchTable($users->first()->name)
    ->assertCanSeeTableRecords($users->take(1))
    ->assertCanNotSeeTableRecords($users->skip(1));

</code-snippet>

<code-snippet name="Create resource test" lang="php">
use function Pest\Laravel\assertDatabaseHas;

livewire(CreateUser::class)
    ->fillForm([
        'name' => 'Test',
        'email' => 'test@example.com',
    ])
    ->call('create')
    ->assertNotified()
    ->assertHasNoFormErrors()
    ->assertRedirect();

assertDatabaseHas(User::class, [
    'name' => 'Test',
    'email' => 'test@example.com',
]);

</code-snippet>

<code-snippet name="Edit resource test" lang="php">
livewire(EditUser::class, ['record' => $user->id])
    ->fillForm(['name' => 'Updated'])
    ->call('save')
    ->assertNotified()
    ->assertHasNoFormErrors();

assertDatabaseHas(User::class, [
    'id' => $user->id,
    'name' => 'Updated',
]);

</code-snippet>

<code-snippet name="Testing validation" lang="php">
livewire(CreateUser::class)
    ->fillForm([
        'name' => null,
        'email' => 'invalid-email',
    ])
    ->call('create')
    ->assertHasFormErrors([
        'name' => 'required',
        'email' => 'email',
    ])
    ->assertNotNotified();

</code-snippet>

Use `->callAction(DeleteAction::class)` for page actions, or `->callAction(TestAction::make('name')->table($record))` for table actions:

<code-snippet name="Calling actions" lang="php">
use Filament\Actions\Testing\TestAction;

livewire(ListUsers::class)
    ->callAction(TestAction::make('promote')->table($user), [
        'role' => 'admin',
    ])
    ->assertNotified();

</code-snippet>

### Correct Namespaces

- Form fields (`TextInput`, `Select`, `Repeater`, etc.): `Filament\Forms\Components\`
- Infolist entries (`TextEntry`, `IconEntry`, etc.): `Filament\Infolists\Components\`
- Layout components (`Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`, etc.): `Filament\Schemas\Components\`
- Schema utilities (`Get`, `Set`, etc.): `Filament\Schemas\Components\Utilities\`
- Table columns (`TextColumn`, `IconColumn`, etc.): `Filament\Tables\Columns\`
- Table filters (`SelectFilter`, `Filter`, etc.): `Filament\Tables\Filters\`
- Actions (`DeleteAction`, `CreateAction`, etc.): `Filament\Actions\`. Never use `Filament\Tables\Actions\`, `Filament\Forms\Actions\`, or any other sub-namespace for actions.
- Icons: `Filament\Support\Icons\Heroicon` enum (e.g., `Heroicon::PencilSquare`)

### Common Mistakes

- **Never assume public file visibility.** File visibility is `private` by default. Always use `->visibility('public')` when public access is needed.
- **Never assume full-width layout.** `Grid`, `Section`, `Fieldset`, and `Repeater` do not span all columns by default.
- **Use `Select::make('author_id')->relationship('author', 'name')` for BelongsTo fields.** `BelongsToSelect` does not exist in v4.
- **`Repeater` uses `->schema()`, not `->fields()`.**
- **Never add `->dehydrated(false)` to fields that need to be saved.** It strips the value from form state before `->action()` or the save handler runs. Only use it for helper/UI-only fields.
- **Use correct property types when overriding `Page`, `Resource`, and `Widget` properties.** These properties have union types or changed modifiers that must be preserved:
  - `$navigationIcon`: `protected static string | BackedEnum | null` (not `?string`)
  - `$navigationGroup`: `protected static string | UnitEnum | null` (not `?string`)
  - `$view`: `protected string` (not `protected static string`) on `Page` and `Widget` classes

=== kwasii/livewire-mapcn/core rules ===

## livewire-mapcn

This package provides interactive, reactive map components for Laravel Livewire applications, powered by MapLibre GL JS and Alpine.js. It offers Blade components for rendering tile-based maps with markers, popups, routes, clusters, and custom controls, all reactive to Livewire state.

Coordinate order: All coordinates use [lng, lat] order (longitude first). This matches MapLibre GL JS conventions, not the typical [lat, lng] order.

### Installation & Setup

1. Install via Composer: `composer require kwasii/livewire-mapcn`
2. Publish config (optional): `php artisan vendor:publish --tag=livewire-mapcn-config`
3. Publish assets (optional): `php artisan vendor:publish --tag=livewire-mapcn-assets`
4. Add to your Blade layout:

<code-snippet name="Add map styles and scripts to layout" lang="blade">
<head>
    @livewireMapStyles
</head>
<body>
    @livewireScripts
    @livewireMapScripts
</body>
</code-snippet>

Alpine.js and Tailwind CSS must be present — they are required dependencies. Assets are served automatically via package routes so publishing is optional.

### Configuration (config/livewire-mapcn.php)

Key options:

- `default_provider` — default tile provider, default: `carto-positron`
- `dark_provider` — tile provider for dark theme, default: `carto-dark-matter`
- `default_height` — CSS height fallback, default: `full`
- `default_zoom` — initial zoom fallback, default: `7`
- `default_center` — center [lng, lat] fallback, default: `[0, 0]`
- `osrm_url` — OSRM routing server, default: `https://router.project-osrm.org`
- `inject_assets` — `route` (auto via Laravel routes) or `published` (public assets)
- `load_from_cdn` — load MapLibre from CDN, default: `true`
- `maplibre_version` — MapLibre GL JS version, default: `5.19.0`
- `cdn_url` — CDN URL for MapLibre JS (override to self-host)
- `cdn_css_url` — CDN URL for MapLibre CSS (override to self-host)
- `carto_license` — `non-commercial` or `enterprise`
- `cluster_popup_view` — optional Blade view for cluster popups
- `custom_events` — array of MapLibre event names to forward globally as `map:*`

### Built-in Tile Providers

- `carto-positron` — minimal light (default)
- `carto-voyager` — light with road detail
- `carto-dark-matter` — dark
- `osm-raster` — OpenStreetMap raster tiles

For custom tiles, pass a MapLibre style JSON URL to the `style` prop.

### Core Blade Components

All map components must be nested inside `x-map`. All accept standard HTML attributes.

`x-map` — Root map container. Must wrap all other components.

<code-snippet name="Basic map" lang="blade">
<x-map :center="[-0.09, 51.5]" :zoom="13" height="500px" provider="carto-positron">
    <x-map-controls position="top-right" />
</x-map>
</code-snippet>

Key props: `center` ([lng, lat] array), `zoom` (int), `min-zoom`, `max-zoom`, `provider`, `style` (URL), `theme` (auto, light, or dark), `height` (CSS), `width` (CSS), `bearing` (float), `pitch` (float 0-60), `interactive`, `scroll-zoom`, `double-click-zoom`, `drag-pan`, `light-style`, `dark-style`, `events` (additional MapLibre events to forward).

`x-map-controls` — UI controls overlay. Props: `zoom` (bool), `compass` (bool), `locate` (bool), `fullscreen` (bool, default false), `scale` (bool, default false), `position` (top-right, top-left, bottom-right, bottom-left).

`x-map-marker` — Marker at a coordinate. Must be inside `x-map`.

<code-snippet name="Marker with popup and tooltip" lang="blade">
<x-map-marker :lat="51.5" :lng="-0.09" color="#ef4444" :draggable="false">
    <x-marker-label text="London Office" position="top" />
    <x-marker-tooltip text="Hover for info" />
    <x-marker-popup>
        <h3>Our HQ</h3>
        <p>Visit us anytime!</p>
    </x-marker-popup>
</x-map-marker>
</code-snippet>

Key props: `lat` (float, required), `lng` (float, required), `id` (UUID auto-generated), `draggable`, `color` (hex), `anchor` (bottom, top, left, right, center), `offset` ([x, y]), `rotation`, `rotation-alignment`, `pitch-alignment`.

Marker sub-components (used inside `x-map-marker`):
- `x-marker-content` — fully custom HTML marker icon. Props: `class`.
- `x-marker-label` — text label near the marker. Props: `text` (required), `position` (top, bottom, left, right), `class`.
- `x-marker-tooltip` — hover tooltip. Props: `text` (required), `anchor` (default: top), `offset` (default: [0, -10]), `class`.
- `x-marker-popup` — click-to-open popup. Props: `max-width` (default: 300px), `close-button` (default: true), `close-on-click-map`, `close-on-move`, `anchor` (default: bottom), `offset`.

`x-map-popup` — Standalone popup anchored to fixed coordinates.

<code-snippet name="Standalone popup" lang="blade">
<x-map-popup :lat="51.5" :lng="-0.09" :open="true" max-width="300px">
    <p>Custom popup content</p>
</x-map-popup>
</code-snippet>

Props: `lat`, `lng` (required), `open` (default: true), `max-width`, `close-button` (default: false), `close-on-click-map`, `close-on-move`, `anchor`, `offset`.

`x-map-cluster-layer` — Clustered point layer from a PHP array or a GeoJSON URL.

<code-snippet name="Cluster layer with popup slot" lang="blade">
<x-map-cluster-layer :data="$locations" cluster-color="#3b82f6" :cluster-max-zoom="14">
    <x-slot:popup>
        <div class="p-3">
            <h3 class="font-semibold">{name}</h3>
            <p class="text-xs text-gray-500">{address}</p>
        </div>
    </x-slot:popup>
</x-map-cluster-layer>
</code-snippet>

Key props: `data` (array with lat/lng keys), `url` (GeoJSON endpoint URL), `id`, `cluster-max-zoom`, `cluster-radius`, `cluster-min-points`, `cluster-color`, `cluster-text-color`, `cluster-size-stops` (default: [[0,30],[100,40],[1000,50]]), `point-color`, `point-radius`, `show-count`, `popup-property`, `popup-template`, `click-zoom`, `buffer`, `tolerance`, `max-features-to-inline` (default: 2000).

Popup priority: the `x-slot:popup` slot takes highest priority, then `popup-template`, then `popup-property`, then auto lat/lng display.

Use `{propertyName}` placeholders in popup HTML. `{lat}` and `{lng}` are always available.

`x-map-route` — Single route polyline, with optional OSRM directions.

<code-snippet name="Route with OSRM directions" lang="blade">
<x-map-route
    :coordinates="[[-0.12, 51.51], [-0.10, 51.50]]"
    color="#1A56DB"
    :width="4"
    :fetch-directions="true"
    directions-profile="driving"
    :with-stops="true"
    :animate="true"
    :animate-duration="3000"
/>
</code-snippet>

Key props: `coordinates` (array of [lng, lat] pairs, required), `id`, `color`, `width`, `opacity`, `dash-array`, `line-cap`, `line-join`, `active-color`, `active-width`, `hover-color`, `clickable`, `with-stops`, `stop-color`, `fetch-directions`, `directions-profile` (driving, walking, or cycling), `directions-url`, `animate`, `animate-duration`, `active`, `alternatives`, `max-alternatives`, `alternative-color`, `alternative-opacity`, `alternative-width`.

`x-map-route-group` — Multiple selectable routes with click-to-activate behavior. Pair with `x-map-route-list` for a selection UI.

<code-snippet name="Route group with selection panel" lang="blade">
<x-map-route-group
    id="trip-routes"
    :routes="$routes"
    :selected-route="0"
    :fetch-directions="true"
    directions-profile="driving"
    :fit-bounds="true"
    :with-stops="true"
/>
<x-map-route-list
    route-id="trip-routes"
    position="top-left"
    title="Available Routes"
    :show-distance="true"
    :show-duration="true"
    :show-fastest-badge="true"
/>
</code-snippet>

The `$routes` array: each item needs at minimum a `coordinates` key (array of [lng, lat] pairs). Optional per-route overrides: `id`, `color`, `width`.

Key `x-map-route-group` props: `routes` (required), `selected-route` (int or id, default: 0), `fit-bounds`, `alternative-color`, `alternative-opacity`, `alternative-width`, `line-cap`, `line-join`, `clickable`, `fetch-directions`, `directions-profile`, `directions-url`, `animate`, `animate-duration`, `with-stops`, `stop-color`, `active-color`, `active-width`, `hover-color`, `dash-array`.

Key `x-map-route-list` props: `route-id` (matches id of x-map-route-group or x-map-route), `map-id`, `show-distance`, `show-duration`, `show-fastest-badge`, `show-time-diff`, `position` (top-left, top-right, bottom-left, bottom-right), `title`, `width`, `container-class`, `header-class`, `item-class`.

### Livewire Interactivity

Outbound events dispatched to Livewire/Alpine:

Map: `map:loaded`, `map:click` (lat,lng), `map:double-click`, `map:right-click`, `map:move` (throttled 100ms), `map:center-changed`, `map:zoom` (throttled), `map:zoom-changed`, `map:bounds-changed`, `map:drag-end`, `map:bearing-changed` (throttled), `map:pitch-changed` (throttled), `map:style-loaded`.

Locate: `map:locate-success` (lat, lng, accuracy), `map:locate-error`.

Markers: `map:marker-clicked` (id, lat, lng), `map:marker-drag-start`, `map:marker-drag`, `map:marker-drag-end`, `map:marker-mouseenter`, `map:marker-mouseleave`, `map:marker-popup-open` (id), `map:marker-popup-close` (id).

Popups: `map:popup-open` (id), `map:popup-close` (id).

Routes: `map:route-clicked` (id), `map:route-mouseenter`, `map:route-mouseleave`, `map:route-directions-ready` (id, distance, duration), `map:route-directions-error`, `map:route-alternative-selected` (id, alternativeIndex), `map:route-updated` (id).

Route group: `map:route-group-selection-changed` (groupId, routeIndex).

Clusters: `map:cluster-clicked` (clusterId, lat, lng), `map:cluster-expanded`, `map:cluster-point-clicked` (properties, lat, lng).

Route list: `map:route-list-selected` (routeIndex).

Inbound commands dispatched from Livewire to the map:

<code-snippet name="Fly to a location from Livewire" lang="php">
$this->dispatch('map:fly-to', [
    'center' => [-0.09, 51.5],
    'zoom' => 12,
    'essential' => true,
]);
</code-snippet>

Available commands: `map:fly-to` (center, zoom, bearing, pitch, essential), `map:jump-to` (center, zoom, bearing, pitch), `map:fit-bounds` (bounds, padding, maxZoom), `map:set-zoom`, `map:set-bearing`, `map:set-pitch`, `map:set-style`, `map:resize`, `map:force-animate`, `map:call` (method, args). Dynamic data: `map:update-route-data-{id}` (coordinates), `map:update-cluster-data-{id}` (GeoJSON), `map:update-route-group-{id}` (routes, selectedRoute).

### GeoJSON Helper

Use `GeoJSON::fromArray()` to convert PHP arrays into GeoJSON FeatureCollections for dynamic cluster updates:

<code-snippet name="Dynamic cluster update" lang="php">
use Kwasii\LivewireMapcn\Support\GeoJSON;

$this->dispatch(
    "map:update-cluster-data-{$clusterId}",
    GeoJSON::fromArray($filteredLocations)
);
</code-snippet>

The helper accepts items with `lat`/`lng` keys, or raw GeoJSON Feature objects. Extra keys go into `properties`.

### Performance Tips

- Use `x-map-cluster-layer` for 100+ points — never render individual `x-map-marker` at scale.
- Use `map:update-*` commands to push data changes without a full Livewire re-render.
- The `max-features-to-inline` prop (default 2000) controls when cluster data switches from HTML attributes to JS injection.
- Continuous events (`map:move`, `map:zoom`, `map:bearing-changed`, `map:pitch-changed`) are throttled at 100ms. Use `map:center-changed` and `map:zoom-changed` for final post-movement values.
- Set `inject_assets` to `published` and use a CDN for production asset delivery.

=== nativephp/mobile/core rules ===

## NativePHP Mobile

- NativePHP Mobile is a Laravel package for building **fully native** iOS and Android apps with PHP. Screens are
rendered as real SwiftUI (iOS) and Jetpack Compose (Android) UI — driven entirely by PHP via SuperNative components
and EDGE Blade elements. A full PHP runtime runs directly on the device with SQLite — no web server required.
- Documentation: `https://nativephp.com/docs/mobile/4/**`
- IMPORTANT: Always activate the `nativephp-mobile` skill every time you work on any NativePHP functionality.

### Native UI First — Always

**Always build screens with native UI: `NativeComponent` classes registered via `Route::native()`, rendering EDGE
elements (`native:column`, `native:text`, `native:button`, …).** This is the way to build NativePHP apps.

- Never scaffold new screens as web views, Blade-over-WebView pages, Livewire components, or Inertia pages.
- The web view (the `native:web-view` element) is a legacy/edge-case escape hatch for embedding web content — never the
  foundation of a screen. If the user asks for a webview-based screen, build it natively with EDGE instead and
  explain why; only fall back to the web view if they explicitly insist.
- If the app contains legacy webview screens, proactively suggest converting them to native UI (see the
  `nativephp-webview-to-native` skill).
- Style EDGE elements with Tailwind utility classes via `class="..."` / `:class="..."` only — never inline
  CSS `style="..."` attributes or ad-hoc styling props.
- Compose screens from **nested child components**: any `NativeComponent` under `app/NativeComponents` mounts
  as a tag (`UserCard` → `<native:user-card :user="$u" key="user-{{ $u->id }}" @saved="onSaved" />`) with live
  props, its own persistent state, and `emit()` events bubbling to `@event` tag bindings / `#[On('event')]`
  listeners. Prefer extracting a reusable child component over duplicating Blade across screens; give list
  children a stable domain `key` (never the loop index).
- Use `native:icon` (SF Symbols on iOS, Material Icons on Android) for iconography — never emoji characters in
  UI text, labels, or buttons, unless the user explicitly asks for emojis. Prefer the typed icon enums
  (`App\Icons\Ios`, `App\Icons\Android`, `App\Icons\AndroidOutlined`) bound via the `:ios` / `:android`
  attributes, e.g. `:ios="Ios::Gearshape" :android="Android::Settings"`, importing each enum into the view with
  Blade's use directive first. The enums are generated, not shipped — if `app/Icons/` doesn't exist yet, run
  `php artisan native-ui:generate-icons` first (safe to run yourself).

### Theme Tokens, Font Aliases, and Layouts — the Design System Trio

Every app's visual identity belongs in `config/native-ui.php` (publish with
`php artisan vendor:publish --tag=native-ui-config`), not scattered through the markup. When building or
reviewing screens, enforce all three:

1. **Theme tokens over hardcoded colors.** Define the palette once in the config's `theme` block, then style
   with `bg-theme-*` / `text-theme-*` / `border-theme-*` classes (`bg-theme-surface`, `text-theme-on-surface`,
   `border-theme-outline`). Never sprinkle `bg-[#1E2021]`-style arbitrary values for what is really a theme
   role — they can't be re-skinned and don't get automatic dark-mode pairs. Arbitrary color values are for
   genuine data-driven color (per-category identity colors, map imagery, chart series), and those belong in
   one PHP home (an enum or model method), never inline per view. Two capabilities that prevent hex fallbacks:
   - **The token map is open-ended.** When a design needs a role the shipped set lacks (a success green, an
     `outline-variant`), add it to both `light` and `dark` blocks — `bg-theme-success` works immediately; no
     package change required.
   - **Theme classes take opacity modifiers** just like palette classes: `bg-theme-primary/15` is the correct
     tonal-fill idiom (applies to the dark companion too) — never approximate with a hardcoded alpha hex.
2. **Font aliases over file tokens.** Register semantic aliases in the config's `fonts` array
   (`'headline' => 'ArchivoNarrow-Bold'`, `'mono' => 'JetBrainsMono-Regular'`, `'default' => …` for the
   app-wide font) and write `font="headline"` in views — never `font="ArchivoNarrow-Bold"`. Swapping a
   typeface must be a one-line config change.
3. **Native chrome via composable chrome elements (layouts optional).** Author nav bars, tab bars, fabs, and
   side navs directly in the screen's Blade — `<native:top-bar>` (+ `top-bar-action`), `<native:bottom-nav>`
   (+ `bottom-nav-item`), `<native:fab>`, `<native:bottom-bar>`, `<native:side-nav>`. They hoist onto the real
   NavigationStack/TabView chrome (edge-swipe back, predictive back, large titles, Liquid Glass/Material You),
   and their attributes are Blade expressions over screen state, so badges/subtitles/icons are reactive. A
   `NativeLayout` (attached via `Route::native(...)->layout(...)` or `Route::nativeGroup(...)`) is **optional**
   — reach for one only when many screens share identical chrome (e.g. one tabs layout for a tab section); an
   inline chrome element on a screen always overrides the layout's bar for that slot. Add the `custom`
   attribute to a chrome tag only for designs the system bars genuinely can't express — it renders in-tree as
   an ordinary drawn element. Never hand-roll top bars or bottom navs out of rows and pressables — that
   forfeits native back gestures, safe-area handling, and Liquid Glass/Material You. Chrome colors take theme
   tokens (inline: theme classes / `theme()`-fed attributes; builders: `->activeColor(theme('primary'))`) —
   never pasted hex. Bar icons take the platform enums via `:ios-icon` / `:android-icon` with a plain `icon`
   string as cross-platform fallback; bar fonts take config aliases (`font="mono"` / `->font('mono')`). Only
   screens rendered without any chrome (no layout AND no inline bars) may use `safe-area` classes.

### When a Capability Is Missing

If the app needs native functionality or a UI component that core and `native-ui` don't provide:

1. **Look for an existing plugin first.** Check the plugin marketplace (`https://plugins.nativephp.com`) and the
   official core plugins. (If a marketplace-lookup MCP tool is available in your session, use it.)
2. **If no plugin exists, build a custom plugin** with `php artisan native:plugin:create` — plugins bundle
   Swift/Kotlin bridge functions, events, permissions, and can even ship their own native EDGE components.
3. **Never fall back to the web view to fill a native gap.** A missing capability is a reason to write a plugin,
   not a reason to build a webview screen.

### Installing Plugins — Always Register and Verify

Requiring a plugin with Composer is NOT enough — an installed-but-unregistered plugin does nothing. Every plugin
install must follow all three steps:

1. `composer require vendor/plugin-name`
2. `php artisan vendor:publish --tag=nativephp-plugins-provider` — publishes the app's `NativeServiceProvider`
   (needed once, before the first plugin registration; harmless to re-run)
3. `php artisan native:plugin:register vendor/plugin-name` — adds it to the `NativeServiceProvider`
4. `php artisan native:plugin:list` — verify it shows as registered

Then tell the user to rebuild with `php artisan native:run` (native code only compiles in at build time — do not
run this yourself). If `native:run` warns "The following plugins are installed but not registered", go back to
step 3.

### Database Seeding — Always via Migrations

On-device there is no `db:seed` — NativePHP runs **migrations** on app start (once each, tracked, versioned).
Whenever asked to seed the database, use the migration trick: create a dedicated migration
(`php artisan make:migration seed_app_settings`) and put the inserts in `up()`. If a Seeder class helps organize
the data, still create it — but invoke it **from the migration's `up()`** (e.g. `(new CategorySeeder)->run()`),
never rely on `db:seed` being run. Seed migrations must be safe for both fresh installs and updates of existing
user databases.

### Build Commands — Tell the User, Never Run

**CRITICAL: Never execute any of these commands yourself. Always instruct the user to run them manually in their
terminal.**

| Command | Purpose |
|---|---|
| `php artisan native:run ios` | Compile and run on iOS simulator/device |
| `php artisan native:run android` | Compile and run on Android emulator/device |
| `php artisan native:run ios --watch` | Build, deploy, then start hot reload — all in one |
| `php artisan native:watch` | Hot reload (watch for file changes) |
| `php artisan native:open` | Open project in Xcode or Android Studio |
| `php artisan native:install` | Install/upgrade the native shell |

Notes:
- The `./native` shortcut wraps the `native:` namespace (`./native run`, `./native watch`).
- The Vite dev server is **opt-in** in v4: add `--vite` to `native:run`/`native:watch` only when the app actually
  uses JS/CSS HMR. Native UI screens hot-reload without Vite.
- `npm run build -- --mode=ios|android` is only needed for apps with web-view assets — not for native UI screens.

**Always ask which platform before giving any build or run command.** If the user hasn't specified iOS or Android,
ask: "Which platform do you want to build/test on — iOS or Android?" Never assume a platform.

When the platform is confirmed, give the relevant command(s) above and tell the user to run it in their terminal.
Do not run it yourself.

=== nativephp/mobile-camera/core rules ===

## nativephp/camera

Camera plugin for NativePHP Mobile providing photo capture, video recording, and gallery picker functionality.

### PHP Usage (Livewire/Blade)

<code-snippet name="Taking Photos" lang="php">
use Native\Mobile\Facades\Camera;

// Take a photo
Camera::getPhoto();
</code-snippet>

<code-snippet name="Recording Videos" lang="php">
use Native\Mobile\Facades\Camera;

// Using fluent API
Camera::recordVideo()
    ->maxDuration(60)
    ->id('my-video-123')
    ->start();
</code-snippet>

<code-snippet name="Picking Media from Gallery" lang="php">
use Native\Mobile\Facades\Camera;

// Pick multiple images
Camera::pickImages('images', true);

// Pick any media type
Camera::pickImages('all', true);
</code-snippet>

### JavaScript Usage (Vue/React/Inertia)

<code-snippet name="Camera in JavaScript" lang="javascript">
import { camera } from '#nativephp';

// Take a photo with identifier
await camera.getPhoto().id('profile-pic');

// Record video with max duration
await camera.recordVideo()
    .maxDuration(30)
    .id('my-video-123');

// Pick multiple images from gallery
await camera.pickImages()
    .images()
    .multiple()
    .maxItems(5);
</code-snippet>

### Handling Camera Events

#### PHP

<code-snippet name="Photo Events" lang="php">
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Camera\PhotoTaken;

#[OnNative(PhotoTaken::class)]
public function handlePhotoTaken(string $path)
{
    $this->processPhoto($path);
}
</code-snippet>

<code-snippet name="Video Events" lang="php">
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Camera\VideoRecorded;

#[OnNative(VideoRecorded::class)]
public function handleVideoRecorded(string $path, string $mimeType, ?string $id = null)
{
    $this->processVideo($path);
}
</code-snippet>

<code-snippet name="Gallery Events" lang="php">
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Gallery\MediaSelected;

#[OnNative(MediaSelected::class)]
public function handleMediaSelected(array $media)
{
    foreach ($media as $file) {
        $this->processMedia($file);
    }
}
</code-snippet>

### Events

- `Native\Mobile\Events\Camera\PhotoTaken` - Photo captured (payload: `string $path`)
- `Native\Mobile\Events\Camera\VideoRecorded` - Video recorded (payload: `string $path`, `string $mimeType`, `?string $id`)
- `Native\Mobile\Events\Camera\VideoCancelled` - Recording cancelled
- `Native\Mobile\Events\Gallery\MediaSelected` - Media selected (payload: `array $media`)

### Storage Locations

- **Photos (Android):** `{cache}/captured.jpg`
- **Photos (iOS):** `~/Library/Application Support/Photos/captured.jpg`
- **Videos (Android):** `{cache}/video_{timestamp}.mp4`
- **Videos (iOS):** `~/Library/Application Support/Videos/captured_video_{timestamp}.mp4`

=== filament/blueprint/core rules ===

## Filament Blueprint

You are writing Filament v5 implementation plans. Plans must be specific enough
that an implementing agent can write code without making decisions.

**Start here**: Read
`/vendor/filament/blueprint/resources/markdown/planning/overview.md` for plan format,
required sections, and what to clarify with the user before planning.

=== laraveldaily/filacheck/core rules ===

## laraveldaily/filacheck

- After you have created/modified any files in `app/Filament` folder, you must run `vendor/bin/filacheck --fix`, to ensure there is no deprecated Filament code. Reported not fixed issues MUST be fixed before continuing.

</laravel-boost-guidelines>
