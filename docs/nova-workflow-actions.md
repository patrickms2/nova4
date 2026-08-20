# Nova Workflow Actions

Acciones PHP registradas en el sistema de workflows del Filament Agentic Chatbot.
Uso: nodo `Action` en el editor visual → campo `actionKey`.

> **Sin duplicación**: Toda la información vive en nuestros modelos propios.
> Los workflows leen directamente `nova_ai_knowledge`, `servers`, `tools` — no hay datos en `rag_chunks`.

---

## `search-nova-knowledge`

Busca en `nova_ai_knowledge` (fuente única de verdad).

**inputMapping:**
```json
{
  "message": "{{user_message}}",
  "business_slug": "la-geria",
  "limit": 5
}
```
O bien por ID:
```json
{ "message": "{{user_message}}", "nova_business_id": 2 }
```

**Output variable:** `knowledge_results`  
**Estructura:** `{ count, results: [{ title, content, score }] }`

---

## `call-mcp-tool`

Llama una tool de un MCP Server local via JSON-RPC.

**inputMapping:**
```json
{
  "server_slug": "lageria-shop-tours-mcp",
  "tool_name": "lageria-latepoint-list-services",
  "input": {}
}
```

**Output variable:** `tool_result`  
**Estructura:** `{ result: [...] }` o `{ error: "..." }`

---

## `list-mcp-tools`

Lista las tools disponibles de un negocio o servidor.

**inputMapping:**
```json
{ "nova_business_id": 2 }
```
O por servidor:
```json
{ "server_slug": "taxilanz-hoteles-laravel-mcp" }
```

**Output variable:** `tools_list`  
**Estructura:** `{ count, tools: [{ server, name, title, description }] }`

---

## Modelos — quién edita qué

| Datos              | Tabla                    | Admin UI                              |
|--------------------|--------------------------|---------------------------------------|
| Conocimiento IA    | `nova_ai_knowledge`      | NovaBusiness → Conocimiento IA        |
| Tools MCP          | `tools`                  | NovaBusiness → Tools / Admin → Tools  |
| Servidores MCP     | `servers`                | NovaBusiness → MCP / Admin → Servers  |
| Listing config     | `nova_listing_categories`| NovaBusiness → Listing Config         |
| Intent rules       | `nova_intent_rules`      | NovaBusiness → Intents                |
| Bots / Workflows   | `rag_bots` + `agent_workflows` | Admin → Bots (plugin)           |

---

## Workflow recomendado por negocio

```
Trigger: User Message
  ↓
Action: search-nova-knowledge  (busca info relevante)
  ↓
Action: call-mcp-tool          (obtiene datos en tiempo real)
  ↓
AI Agent: genera respuesta con contexto
  ↓
Send Message: respuesta al usuario
```

Ver ejemplos en `docs/nova-workflow-examples/`.
