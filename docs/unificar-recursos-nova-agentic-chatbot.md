# Unificar Recursos Nova y Agentic Chatbot

> **Objetivo**: Usar los recursos existentes de Nova (knowledge, intents) en lugar de duplicarlos en Agentic Chatbot

---

## Estado Actual: ✅ YA UNIFICADO

Los recursos de Nova ya están integrados en las acciones de workflow de Agentic Chatbot. No hay duplicación.

---

## Recursos Nova Utilizados

### 1. `NovaIntentRule` ✅
- **Usado en:** `app/Actions/Workflow/DetectNovaIntentAction.php`
- **Función:** Reglas de detección de intención
- **Estado:** Ya unificado. La acción `detect-nova-intent` consulta `NovaIntentRule` de Nova.

```php
// DetectNovaIntentAction.php
$intentRules = NovaIntentRule::where('is_active', true)->get();
```

### 2. `NovaAiKnowledge` ✅
- **Usado en:** `app/Actions/Workflow/SearchNovaKnowledgeAction.php`
- **Función:** Base de conocimiento para RAG
- **Estado:** Ya unificado. La acción `search-nova-knowledge` usa `NovaKnowledgeService` que consulta `NovaAiKnowledge` de Nova.

```php
// SearchNovaKnowledgeAction.php
$results = $this->knowledge->relevantKnowledge($business, $message, $limit);

// NovaKnowledgeService.php
$records = NovaAiKnowledge::query()
    ->where('nova_business_id', $business->id)
    ->where('status', 'active')
    ->get();
```

### 3. `NovaIntentToServerMapping` ✅
- **Usado en:** `app/Actions/Workflow/DetectNovaIntentAction.php`
- **Función:** Mapeo intención → MCP server
- **Estado:** Ya unificado. La acción `detect-nova-intent` consulta `NovaIntentToServerMapping` de Nova.

```php
// DetectNovaIntentAction.php
$mapping = NovaIntentToServerMapping::where('intent_key', $intent)
    ->where('is_active', true)
    ->first();
```

---

## Flujo de Datos Unificado

```
Usuario → Agentic Chatbot Widget
  ↓
Workflow Visual (Agentic Chatbot)
  ↓
Action: detect-nova-intent
  ├─ Consulta NovaIntentRule (Nova)
  ├─ Consulta NovaIntentToServerMapping (Nova)
  └─ Retorna: intent, server_slug, tool_name
  ↓
Action: search-nova-knowledge
  ├─ Consulta NovaAiKnowledge (Nova)
  └─ Retorna: conocimiento relevante
  ↓
Action: call-mcp-tool
  └─ Ejecuta tool del MCP externo
  ↓
Nova MCP Layer
  ├─ Normaliza respuesta
  └─ Registra en Nova
  ↓
Filament (Vista Unificada)
```

---

## Configuración en Filament

Todos los recursos se configuran desde Filament usando los recursos de Nova:

### 1. Nova Intent Rules
- **Ruta:** Filament → Nova Intent Rules
- **Configuración:** Keywords, prioridades, reglas de intención
- **Usado por:** `detect-nova-intent` action

### 2. Nova AI Knowledge
- **Ruta:** Filament → Nova AI Knowledge
- **Configuración:** Documentos, embeddings, status
- **Usado por:** `search-nova-knowledge` action

### 3. Nova Intent to Server Mapping
- **Ruta:** Filament → Nova Intent to Server Mapping
- **Configuración:** Mapeo intención → server + tool
- **Usado por:** `detect-nova-intent` action

---

## Ventajas de la Unificación

1. **Sin duplicación:** Un solo set de recursos para Nova y Agentic Chatbot
2. **Configuración centralizada:** Todo en Filament
3. **Consistencia:** Mismos datos para todos los sistemas
4. **Mantenimiento simplificado:** Cambios en un solo lugar
5. **Escalabilidad:** Fácil añadir nuevos intents y conocimiento

---

## Documentos Relacionados

- `docs/guides/configurar-casos-uso-reales-filament.md` — Configurar intents en Filament
- `docs/guides/crear-workflow-mcp-filament.md` — Crear workflows visuales
- `docs/guides/jerarquia-nova-mcp-servers.md` — Jerarquía visual<tool_call>read<arg_key>file_path</arg_key><arg_value>/Users/Shared/WWW/novahubmcp/app/Actions/Workflow/SearchNovaKnowledgeAction.php
