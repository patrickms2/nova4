# Flujo Completo Nova + Agentic Chatbot

> **Arquitectura integrada**: Usuario → Chatbot → Workflow → Nova MCP → MCP Externo → Filament

---

## Diagrama del Flujo Completo

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              USUARIO FINAL                                  │
└──────────────────────────────┬──────────────────────────────────────────────┘
                               │
                               │ "Quiero reservar mesa mañana a las 20"
                               ↓
┌─────────────────────────────────────────────────────────────────────────────┐
│                        AGENTIC CHATBOT WIDGET                                │
│  - Chat embed en website                                                   │
│  - WhatsApp Cloud API                                                       │
│  - Telegram, Slack                                                          │
└──────────────────────────────┬──────────────────────────────────────────────┘
                               │
                               ↓
┌─────────────────────────────────────────────────────────────────────────────┐
│                          PARENT AGENT                                        │
│  - Orquesta memoria conversacional                                          │
│  - Gestiona knowledge search                                                │
│  - Ejecuta workflow visual                                                  │
└──────────────────────────────┬──────────────────────────────────────────────┘
                               │
                               ↓
┌─────────────────────────────────────────────────────────────────────────────┐
│                      WORKFLOW VISUAL (Agentic Chatbot)                        │
│  ┌──────────────────────────────────────────────────────────────────────┐  │
│  │  Trigger: User Message                                                │  │
│  └────────────────────────────────┬─────────────────────────────────────┘  │
│                                   ↓                                          │
│  ┌──────────────────────────────────────────────────────────────────────┐  │
│  │  Action: detect-nova-intent                                          │  │
│  │  - Usa NovaIntentRule (Filament)                                     │  │
│  │  - Retorna: intent, server_slug, tool_name                           │  │
│  └────────────────────────────────┬─────────────────────────────────────┘  │
│                                   ↓                                          │
│  ┌──────────────────────────────────────────────────────────────────────┐  │
│  │  Action: call-mcp-tool                                              │  │
│  │  - server_slug: sirvo-restaurants-mcp                               │  │
│  │  - tool_name: sirvo-restaurantes                                     │  │
│  └────────────────────────────────┬─────────────────────────────────────┘  │
│                                   ↓                                          │
│  ┌──────────────────────────────────────────────────────────────────────┐  │
│  │  Action: normalize-mcp-response                                    │  │
│  │  - Convierte respuesta Sirvo → estructura Nova                       │  │
│  └────────────────────────────────┬─────────────────────────────────────┘  │
│                                   ↓                                          │
│  ┌──────────────────────────────────────────────────────────────────────┐  │
│  │  Action: register-nova-data                                         │  │
│  │  - Registra en nova_external_bookings                               │  │
│  └────────────────────────────────┬─────────────────────────────────────┘  │
│                                   ↓                                          │
│  ┌──────────────────────────────────────────────────────────────────────┐  │
│  │  End                                                                  │  │
│  └──────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────┬──────────────────────────────────────────────┘
                               │
                               ↓
┌─────────────────────────────────────────────────────────────────────────────┐
│                      NOVA MCP ROUTER (Nova)                                 │
│  - Detecta intención usando NovaIntentRule                                │
│  - Consulta mapeo intención → server (NovaIntentToServerMapping)            │
│  - Enruta petición al MCP externo correcto                                 │
└──────────────────────────────┬──────────────────────────────────────────────┘
                               │
                               ↓
┌─────────────────────────────────────────────────────────────────────────────┐
│                      MCP EXTERNO (Sirvo)                                    │
│  - Sirvo MCP Server                                                        │
│  - Tool: sirvo-restaurantes                                               │
│  - API Sirvo → Crear reserva                                              │
└──────────────────────────────┬──────────────────────────────────────────────┘
                               │
                               ↓
┌─────────────────────────────────────────────────────────────────────────────┐
│                      NOVA NORMALIZER (Nova)                                │
│  - Convierte estructura Sirvo → estructura Nova                            │
│  - Aplica reglas de normalización (configurables en Filament)              │
└──────────────────────────────┬──────────────────────────────────────────────┘
                               │
                               ↓
┌─────────────────────────────────────────────────────────────────────────────┐
│                      NOVA REGISTRATION (Nova)                                │
│  - Crea registro en nova_external_bookings                                 │
│  - Asocia con intent_key y server_id                                       │
└──────────────────────────────┬──────────────────────────────────────────────┘
                               │
                               ↓
┌─────────────────────────────────────────────────────────────────────────────┐
│                          FILAMENT (Vista Unificada)                           │
│  ┌──────────────────────────────────────────────────────────────────────┐  │
│  │  Nova Intent Rules                                                    │  │
│  │  - Configurar reglas de detección de intención                       │  │
│  │  - Keywords, patrones, prioridades                                     │  │
│  └──────────────────────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────────────────────┐  │
│  │  Nova Intent to Server Mapping                                       │  │
│  │  - Mapear intención → MCP server + tool                              │  │
│  │  - Ejemplo: restaurant_booking → sirvo-restaurants-mcp                │  │
│  └──────────────────────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────────────────────┐  │
│  │  Nova External Bookings                                              │  │
│  │  - Vista unificada de todas las reservas                             │  │
│  │  - Filtrable por server, intent, fecha, estado                        │  │
│  └──────────────────────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────────────────────┐  │
│  │  Nova AI Knowledge                                                    │  │
│  │  - Knowledge base para RAG                                           │  │
│  │  - Documentos por negocio (Sirvo, La Geria, etc.)                     │  │
│  └──────────────────────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────────────────────┐  │
│  │  Agentic Chatbot Workflows                                            │  │
│  │  - Editor visual de workflows                                        │  │
│  │  - Nodos MCP personalizados                                           │  │
│  └──────────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Ejemplo Práctico: Reserva de Restaurante

### Paso 1: Usuario envía mensaje
```
Usuario: "Quiero reservar mesa mañana a las 20 para 4 personas"
```

### Paso 2: Agentic Chatbot Widget recibe mensaje
- Widget web o WhatsApp
- Envia al Parent Agent

### Paso 3: Parent Agent inicia workflow
- Busca workflow asignado al bot "Nova MCP Operator"
- Ejecuta workflow "Nova Master Router"

### Paso 4: Workflow Visual - Detectar Intención
```
Action: detect-nova-intent
Input: {"message": "Quiero reservar mesa mañana a las 20 para 4 personas"}
Output: {
  "intent": "restaurant_booking",
  "server_slug": "sirvo-restaurants-mcp",
  "tool_name": "sirvo-restaurantes",
  "confidence": 0.9
}
```

### Paso 5: Workflow Visual - Ejecutar MCP Tool
```
Action: call-mcp-tool
Input: {
  "server_slug": "sirvo-restaurants-mcp",
  "tool_name": "sirvo-restaurantes",
  "input": {
    "date": "2026-06-19",
    "time": "20:00",
    "guests": 4
  }
}
Output: {
  "result": {
    "booking_id": "RES-12345",
    "restaurant": "La Bodeguita",
    "confirmation": "Reserva confirmada"
  }
}
```

### Paso 6: Workflow Visual - Normalizar Respuesta
```
Action: normalize-mcp-response
Input: {
  "raw_response": {...},
  "server_slug": "sirvo-restaurants-mcp",
  "response_type": "booking"
}
Output: {
  "normalized": {
    "source": "Sirvo Restaurants MCP",
    "type": "booking",
    "data": {
      "booking_id": "RES-12345",
      "restaurant_name": "La Bodeguita",
      "date": "2026-06-19",
      "time": "20:00",
      "guests": 4,
      "status": "confirmed"
    }
  }
}
```

### Paso 7: Workflow Visual - Registrar en Nova
```
Action: register-nova-data
Input: {
  "normalized_data": {...},
  "server_slug": "sirvo-restaurants-mcp",
  "record_type": "booking",
  "intent_key": "restaurant_booking"
}
Output: {
  "record_id": 789,
  "record_type": "booking",
  "source": "Sirvo Restaurants MCP"
}
```

### Paso 8: Filament - Vista Unificada
- Registro visible en: Filament → Nova External Bookings
- Datos normalizados y estructurados
- Asociado con intent "restaurant_booking"
- Asociado con server "sirvo-restaurants-mcp"

### Paso 9: Respuesta al Usuario
```
Agentic Chatbot: "✅ Reserva confirmada en La Bodeguita para mañana a las 20:00 para 4 personas.
                 Número de reserva: RES-12345"
```

---

## Componentes Clave

### 1. Agentic Chatbot (Capa Visual No-Code)
- **Widget**: Chat embed para websites
- **Parent Agent**: Orquestador de memoria y workflows
- **Workflow Builder**: Editor visual de workflows
- **Custom Actions**: `call-mcp-tool`, `detect-nova-intent`, `normalize-mcp-response`, `register-nova-data`

### 2. Nova MCP (Router + Normalizador Central)
- **NovaIntentRule**: Reglas de detección de intención (configurables en Filament)
- **NovaIntentToServerMapping**: Mapeo intención → MCP server (configurable en Filament)
- **NovaResponseNormalizer**: Normalizador de respuestas MCP
- **NovaRegistrationService**: Registro en tablas Nova

### 3. MCP Externos (Agentes IA)
- **Sirvo MCP**: Reservas de restaurantes
- **La Geria MCP**: Visitas bodega
- **Taxilanz MCP**: Hoteles, transfers
- **Lanzaloe MCP**: Productos Magento

### 4. Filament (Panel de Administración)
- **Nova Intent Rules**: Configurar reglas de intención
- **Nova Intent to Server Mapping**: Configurar mapeos
- **Nova External Bookings**: Vista unificada de reservas
- **Nova AI Knowledge**: Knowledge base para RAG
- **Agentic Chatbot Workflows**: Editor visual de workflows

---

## Ventajas de esta Arquitectura

1. **Configuración 100% desde Filament**: Sin código hardcoded
2. **Visualización clara de integraciones**: Workflows visuales muestran dependencias
3. **Flexibilidad extrema**: Añadir nuevos MCP servers sin tocar código
4. **Normalización centralizada**: Una sola lógica para todos los MCPs
5. **Vista unificada**: Todos los datos normalizados en Filament
6. **Debugging visual**: Ver flujo completo en el editor de workflows

---

---

## Documentos Relacionados

- `docs/NOVA_ARCHITECTURE_MASTER.md` — Arquitectura maestra v2.0
- `docs/plans/nova-agentic-chatbot-integration.md` — Plan de integración
- `docs/guides/crear-workflow-mcp-filament.md` — Guía paso a paso
- `docs/nova-workflow-examples/nova-master-router.json` — Workflow maestro
