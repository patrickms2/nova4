# Forms, Selects, Repeaters, and Uploads

## Large Option Lists

Static option arrays are fine for small enums and fixed choices. They become a performance and usability problem when they represent database-sized data.

Flag:

- `Select`, `CheckboxList`, or `Radio` with large inline arrays.
- `options()` closures that query on every hydration or render.
- Relationship selects without `searchable()` when the related table can grow.
- `preload()` on relationship selects backed by large tables.

Recommended fixes:

- Use relationship-backed `Select::make(...)->relationship(...)->searchable()`.
- Use `preload()` only for genuinely small datasets.
- Limit searchable fields to useful columns, for example `->searchable(['name', 'email'])`.
- Cache fixed option lists with `Cache::remember()` or `once()` when values rarely change.

## Reactive and Livewire Hydration Cost

Filament forms run inside Livewire. Large state trees and overly eager updates can dominate response time.

Flag:

- Many fields using `live()` when only blur-level updates are needed.
- Text inputs with `live()` instead of `live(onBlur: true)` for slug or derived-field updates.
- Nested repeaters with relationship data and many fields per item.
- Expensive `visible()`, `hidden()`, `disabled()`, `options()`, validation, or label callbacks.
- Flat schemas with many fields and no layout grouping when the resulting page becomes hard to scan and slow to hydrate.

Recommended fixes:

- Use `live(onBlur: true)` for text fields unless per-keystroke behavior is required.
- Move repeated expensive lookups outside callbacks or cache them per request.
- Break large schemas into sections, tabs, or steps when it reduces initial rendered state.
- Avoid unnecessary relationship repeater nesting for large child collections.

## File Uploads

File uploads have both performance and safety costs.

Flag:

- Missing `acceptedFileTypes()` when uploads should be constrained.
- Missing `maxSize()` for uploads.
- Image processing or transformations in the request path when they should be queued.
- Public visibility assumptions. Filament file visibility is private unless configured otherwise.

Recommended fixes:

- Add explicit accepted MIME types and max size.
- Queue heavy image processing or conversion work.
- Use public visibility only when public access is actually required.

## Form Findings

When reporting a form issue, include whether the risk is initial render cost, hydration payload size, query repetition, user searchability, upload safety, or all of these.
