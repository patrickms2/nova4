# Crear Workflow MCP en Filament — Guía Rápida

> **Objetivo**: Crear un workflow visual en Filament que use MCP servers
> **Tiempo estimado**: 5-10 minutos

---

## Prerrequisitos

- Agentic Chatbot instalado y configurado ✅
- Bot "Nova MCP Operator" creado ✅
- MCP servers activos con tools ✅
- Acción `call-mcp-tool` configurada en `config/filament-agentic-chatbot.php` ✅

---

## Paso 1: Acceder a Filament

1. Abrir el panel de Filament: `https://novahubmcp.test/admin`
2. Iniciar sesión
3. Navegar a **Agentic Chatbot** → **Workflows**

---

## Paso 2: Crear Nuevo Workflow

1. Click en **Create** (nuevo workflow)
2. Configurar:
   - **Name**: `Nova MCP Test - Hotel List`
   - **Description**: `Workflow de prueba para listar hoteles via MCP`
   - **Bot**: `Nova MCP Operator`
   - **Is Active**: ✅
3. Click en **Create**

---

## Paso 3: Diseñar Workflow Visual

### Añadir Nodo Start

1. Click en **Add Node** → **Start**
2. Arrastrar a posición: x=100, y=100
3. Label: `Start`

### Añadir Nodo Action (MCP)

1. Click en **Add Node** → **Action**
2. Arrastrar a posición: x=300, y=100
3. Configurar:
   - **Label**: `Call MCP Tool`
   - **Action Key**: `call-mcp-tool`
   - **Input Mapping**:
     ```json
     {
       "server_slug": "taxilanz-hoteles-laravel",
       "tool_name": "hotel_list",
       "input": {}
     }
     ```
4. Click en **Save**

### Añadir Nodo Action (Normalización) - Opcional

1. Click en **Add Node** → **Action**
2. Arrastrar a posición: x=500, y=100
3. Configurar:
   - **Label**: `Normalize Response`
   - **Action Key**: `normalize-mcp-response`
   - **Input Mapping**:
     ```json
     {
       "raw_response": "{{mcp_action.result}}",
       "server_slug": "taxilanz-hoteles-laravel",
       "response_type": "booking"
     }
     ```
4. Click en **Save**

### Añadir Nodo Action (Registro) - Opcional

1. Click en **Add Node** → **Action**
2. Arrastrar a posición: x=700, y=100
3. Configurar:
   - **Label**: `Register in Nova`
   - **Action Key**: `register-nova-data`
   - **Input Mapping**:
     ```json
     {
       "normalized_data": "{{normalize_action.normalized}}",
       "server_slug": "taxilanz-hoteles-laravel",
       "record_type": "booking",
       "intent_key": "hotel_booking"
     }
     ```
4. Click en **Save**

### Añadir Nodo End

1. Click en **Add Node** → **End**
2. Arrastrar a posición: x=900, y=100
3. Label: `End`

### Conectar Nodos

1. Click en **Add Edge**
2. Conectar: `Start` → `Call MCP Tool`
3. Conectar: `Call MCP Tool` → `Normalize Response` (si existe)
4. Conectar: `Normalize Response` → `Register in Nova` (si existe)
5. Conectar: `Register in Nova` → `End` (si existe)
6. Si no hay normalización/registro: `Call MCP Tool` → `End`

---

## Paso 4: Publicar Workflow

1. Click en **Publish** (botón en la esquina superior derecha)
2. Confirmar publicación
3. El workflow ahora está activo y puede ser usado

---

## Paso 5: Probar Workflow

### Opción A: Via Widget

1. Navegar a **Agentic Chatbot** → **Bots**
2. Click en botón **Test** del bot "Nova MCP Operator"
3. En el widget de chat, escribir: `lista hoteles`
4. El workflow debería ejecutarse y mostrar los hoteles

### Opción B: Via API

```bash
curl -X POST https://novahubmcp.test/api/filament-agentic-chatbot/chat \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "message": "lista hoteles",
    "bot_public_id": "nova-mcp-operator",
    "session_id": "test-session-123"
  }'
```

---

## Ejemplos de Input Mapping

### Hotel List (Taxilanz)
```json
{
  "server_slug": "taxilanz-hoteles-laravel",
  "tool_name": "hotel_list",
  "input": {}
}
```

### Sirvo Restaurantes
```json
{
  "server_slug": "sirvo-restaurants-mcp",
  "tool_name": "sirvo-restaurantes",
  "input": {
    "date": "2026-06-19",
    "time": "20:00",
    "guests": 4
  }
}
```

### La Geria LatePoint Services
```json
{
  "server_slug": "la-geria-mcp",
  "tool_name": "lageria-latepoint-list-services",
  "input": {}
}
```

### Lanzaloe Magento Products
```json
{
  "server_slug": "lanzaloe-magento",
  "tool_name": "lanzaloe-magento-products",
  "input": {
    "limit": 10
  }
}
```

### Normalizar Respuesta MCP
```json
{
  "raw_response": "{{mcp_action.result}}",
  "server_slug": "taxilanz-hoteles-laravel",
  "response_type": "booking"
}
```

### Registrar Datos en Nova
```json
{
  "normalized_data": "{{normalize_action.normalized}}",
  "server_slug": "taxilanz-hoteles-laravel",
  "record_type": "booking",
  "intent_key": "hotel_booking"
}
```

---

## Troubleshooting

### Error: "Server not found or inactive"
- Verificar que el server_slug es correcto
- Verificar que el server está activo en **MCP Studio** → **Servers**

### Error: "Tool not found on server"
- Verificar que el tool_name es correcto
- Verificar que el tool está activo en el server

### Workflow no se ejecuta
- Verificar que el workflow está **published**
- Verificar que el workflow está **active**
- Verificar que el bot tiene el workflow asignado

### Error en la acción MCP
- Verificar el endpoint del server
- Verificar que el server está respondiendo
- Revisar logs de Laravel: `php artisan pail`

---

## Siguientes Pasos

Una vez probado el workflow básico, puedes:

1. **Añadir más nodos**: Conditions, AI agents, Knowledge base
2. **Crear workflows complejos**: Multi-step, branching, loops
3. **Integrar con Nova MCP**: Normalización, registro en Filament
4. **Añadir canales**: Telegram, Slack, WhatsApp

---

## Documentos Relacionados

- `docs/plans/nova-agentic-chatbot-integration.md` — Plan de integración completo
- `docs/NOVA_ARCHITECTURE_MASTER.md` — Arquitectura maestra
- `docs/MCP/agentic-chatbot-filament-docs-main/AGENTIC_WORKFLOWS.md` — Documentación workflows
