# Elmapi MCP — schema patterns

## Discovering tools

Elmapi MCP tool names and arguments are defined in your environment’s MCP descriptor files. **Read those schemas** before calling tools; do not assume names match the JS SDK method names exactly.

Payload shapes for collections and fields usually align with **`elmapi-collections-fields`** and its [reference.md](../elmapi-collections-fields/reference.md) (for example `is_singleton`, field `type`, `slug`, `required`).

## Mapping common landing sections

| Section | Typical model | Notes |
|--------|----------------|-------|
| Header / nav | Singleton `site-settings` or `layout` | Fields: logo (asset relation or URL), nav items (repeater if MCP supports it, else separate `nav-links` collection) |
| Hero | Singleton `home-hero` or fields on `page-home` | `headline`, `subheadline`, `primary_cta_label`, `primary_cta_url`, optional `background_image` |
| Features | Collection `features` | Repeating: `title`, `description`, `icon` (text or asset), `sort` / `order` |
| Footer | Singleton `site-footer` or `layout` | Copyright, columns, legal links — or collection `footer-links` for many rows |

Prefer **fewer, well-named collections** over one giant singleton with dozens of unrelated fields unless the product team wants a single “Globals” document.

## Slugs and the app

Use the **same slugs** in MCP/SDK operations and in `content.list` / `content.get` calls. Changing slugs later breaks the app unless you migrate.

## Richtext

If body copy is long or formatted, use a `richtext` field. Set **`options.editor.mode`** intentionally: use **`markdown`** when you will seed entries with markdown strings via MCP; leave default **`lexical`** only if you will write HTML or `{ html, json }`. Never put markdown into a lexical field. See **`elmapi-collections-fields`** and **`elmapi-content`**.
