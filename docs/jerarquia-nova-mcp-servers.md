# Jerarquía Nova y Conexiones MCP Servers

> **Arquitectura visual**: Cómo se estructura Nova y se conecta con MCP servers externos

---

## Diagrama de Jerarquía Nova

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              NOVA (Sistema Central)                           │
└─────────────────────────────────────────────────────────────────────────────┘
                                       │
        ┌──────────────────────────────┼──────────────────────────────┐
        │                              │                              │
        ↓                              ↓                              ↓
┌──────────────────────┐    ┌──────────────────────┐    ┌──────────────────────┐
│   NOVA AI LAYER      │    │   NOVA MCP LAYER     │    │   FILAMENT LAYER     │
│  (Agentic Chatbot)   │    │  (Router + Normalizer)│    │  (Panel de Admin)    │
└──────────────────────┘    └──────────────────────┘    └──────────────────────┘
        │                              │                              │
        │                              │                              │
        ↓                              ↓                              ↓
┌──────────────────────┐    ┌──────────────────────┐    ┌──────────────────────┐
│ • Bots               │    │ • NovaOrchestrator    │    │ • NovaIntentRules    │
│ • Workflows          │    │ • NovaResponseNormalizer│   │ • IntentToServerMap │
│ • Knowledge Sources  │    │ • NovaRegistration    │    │ • ExternalBookings   │
│ • Widget             │    │ • NovaRouter         │    │ • ExternalOrders     │
│ • Channels (WA, TG)  │    │ • NovaContextManager  │    │ • ExternalTransactions│
└──────────────────────┘    └──────────────────────┘    └──────────────────────┘
        │                              │                              │
        └──────────────────────────────┼──────────────────────────────┘
                                       │
                                       ↓
┌─────────────────────────────────────────────────────────────────────────────┐
│                      NOVA MCP ROUTER (Central)                               │
│  - Detecta intención usando NovaIntentRule                                  │
│  - Consulta mapeo intención → server (NovaIntentToServerMapping)            │
│  - Enruta petición al MCP externo correcto                                   │
└─────────────────────────────────────────────────────────────────────────────┘
                                       │
            ┌──────────────────────────┼──────────────────────────┐
            │                          │                          │
            ↓                          ↓                          ↓
┌──────────────────────┐  ┌──────────────────────┐  ┌──────────────────────┐
│   MCP SERVERS        │  │   MCP SERVERS        │  │   MCP SERVERS        │
│   (Restaurantes)     │  │   (Hoteles)          │  │   (Bodegas)          │
└──────────────────────┘  └──────────────────────┘  └──────────────────────┘
        │                          │                          │
        ↓                          ↓                          ↓
┌──────────────────────┐  ┌──────────────────────┐  ┌──────────────────────┐
│ Sirvo Restaurants    │  │ Taxilanz Hoteles     │  │ La Geria Shop+Tours  │
│ MCP                  │  │ MCP                  │  │ MCP                  │
│ • sirvo-restaurantes │  │ • hotel_list          │  │ • lageria-latepoint   │
│ • sirvo-dashboard    │  │ • servicios_list      │  │ • lageria-woo        │
└──────────────────────┘  └──────────────────────┘  └──────────────────────┘
            │                          │                          │
            └──────────────────────────┼──────────────────────────┘
                                       │
            ┌──────────────────────────┼──────────────────────────┐
            │                          │                          │
            ↓                          ↓                          ↓
┌──────────────────────┐  ┌──────────────────────┐  ┌──────────────────────┐
│   MCP SERVERS        │  │   MCP SERVERS        │  │   MCP SERVERS        │
│   (eCommerce)        │  │   (Transfers)        │  │   (Otros)            │
└──────────────────────┘  └──────────────────────┘  └──────────────────────┘
        │                          │                          │
        ↓                          ↓                          ↓
┌──────────────────────┐  ┌──────────────────────┐  ┌──────────────────────┐
│ Lanzaloe Magento     │  │ Taxilanz Transfers   │  │ PromptlyAgent        │
│ MCP                  │  │ MCP                  │  │ MCP                  │
│ • lanzaloe-magento    │  │ • transfer_locations  │  │ • list_agents        │
│ • lanzaloe-orders     │  │ • transfer_price     │  │ • get_agent_details  │
└──────────────────────┘  └──────────────────────┘  └──────────────────────┘
```

---

## Jerarquía Detallada por Capas

### Capa 1: NOVA AI (Agentic Chatbot)
```
NOVA AI LAYER
├── Bots
│   └── Nova MCP Operator
├── Workflows
│   ├── Nova Master Router
│   ├── Sirvo Restaurantes
│   ├── Taxilanz Hoteles
│   ├── La Geria Agent
│   ├── Lanzaloe Magento
│   └── Taxilanz Transfers
├── Knowledge Sources
│   ├── Sirvo Knowledge
│   ├── La Geria Knowledge
│   ├── Taxilanz Knowledge
│   └── Lanzaloe Knowledge
├── Widget
│   └── Chat Embed
└── Channels
    ├── WhatsApp
    ├── Telegram
    └── Slack
```

### Capa 2: NOVA MCP (Router + Normalizador)
```
NOVA MCP LAYER
├── NovaOrchestratorService
│   ├── Detectar intención
│   ├── Seleccionar server
│   └── Enrutar petición
├── NovaResponseNormalizer
│   ├── Normalizar bookings
│   ├── Normalizar orders
│   └── Normalizar transactions
├── NovaRegistrationService
│   ├── Registrar en nova_external_bookings
│   ├── Registrar en nova_external_orders
│   └── Registrar en nova_external_transactions
└── NovaContextManager
    ├── Mantener estado conversacional
    └── Gestionar memoria
```

### Capa 3: FILAMENT (Panel de Administración)
```
FILAMENT LAYER
├── Nova Intent Rules
│   ├── Configurar reglas de intención
│   ├── Keywords y patrones
│   └── Prioridades
├── Nova Intent to Server Mapping
│   ├── Mapear intención → server
│   ├── Mapear intención → tool
│   └── Configurar parámetros
├── Nova External Bookings
│   ├── Vista unificada de reservas
│   ├── Filtrable por server/intent
│   └── Estados y confirmaciones
├── Nova External Orders
│   ├── Vista unificada de pedidos
│   ├── Filtrable por server/intent
│   └── Estados y confirmaciones
└── Nova External Transactions
    ├── Vista unificada de transacciones
    ├── Filtrable por server/intent
    └── Estados y confirmaciones
```

### Capa 4: MCP SERVERS EXTERNOS
```
MCP SERVERS EXTERNOS
├── Restaurantes
│   └── Sirvo Restaurants MCP
│       ├── sirvo-restaurantes
│       ├── sirvo-dashboard-reservations
│       └── sirvo-config
├── Hoteles
│   └── Taxilanz Hoteles Laravel MCP
│       ├── hotel_list
│       ├── servicios_list
│       └── taxilanz-hoteles-mcp-info
├── Bodegas
│   └── La Geria Shop+Tours MCP
│       ├── lageria-latepoint-list-services
│       ├── lageria-latepoint-run-ability
│       ├── lageria-latepoint-list-bookings
│       └── lageria-woo-products
├── eCommerce
│   └── Lanzaloe Magento MCP
│       ├── lanzaloe-magento-products
│       ├── lanzaloe-magento-orders
│       └── list_knowledge_documents
├── Transfers
│   └── Taxilanz Transfers MCP
│       ├── transfer_locations
│       ├── transfer_price_estimate
│       └── transfer_booking_payload
└── Otros
    ├── Taxilanz Chauffeur Booking MCP
    ├── PromptlyAgent Agent Server
    └── La Geria WordPress Woo LatePoint MCP
```

---

## Flujo de Datos entre Capas

```
USUARIO
  ↓
NOVA AI (Agentic Chatbot)
  ├─ Widget recibe mensaje
  ├─ Parent Agent inicia workflow
  └─ Workflow ejecuta acciones
      ↓
NOVA MCP (Router)
  ├─ Detecta intención (NovaIntentRule)
  ├─ Consulta mapeo (NovaIntentToServerMapping)
  └─ Enruta a MCP externo
      ↓
MCP SERVER EXTERNO
  ├─ Ejecuta tool específica
  ├─ Llama API externa
  └─ Retorna respuesta raw
      ↓
NOVA MCP (Normalizer)
  ├─ Convierte respuesta raw → estructura Nova
  └─ Aplica reglas de normalización
      ↓
NOVA MCP (Registration)
  ├─ Registra en tabla Nova
  └─ Asocia con intent y server
      ↓
FILAMENT
  ├─ Vista unificada de datos
  ├─ Configuración de reglas
  └─ Gestión de mapeos
      ↓
USUARIO
  └─ Respuesta formateada
```

---

## Conexiones Clave

### 1. Nova AI → Nova MCP
- **Workflow Action**: `call-mcp-tool`
- **Workflow Action**: `detect-nova-intent`
- **Workflow Action**: `normalize-mcp-response`
- **Workflow Action**: `register-nova-data`

### 2. Nova MCP → MCP Externos
- **HTTP**: JSON-RPC 2.0
- **Endpoint**: `/mcp/tools/call`
- **Payload**: `{method: "tools/call", params: {name, arguments}}`

### 3. Nova MCP → Filament
- **Database**: Eloquent models
- **Tablas**: `nova_intent_rules`, `nova_intent_to_server_mapping`, `nova_external_bookings`
- **Configuración**: 100% desde Filament

### 4. Filament → Nova AI
- **Configuration**: `config/filament-agentic-chatbot.php`
- **Workflows**: Editor visual en Filament
- **Bots**: Configuración desde Filament

---

## Ventajas de esta Jerarquía

1. **Separación de responsabilidades**: Cada capa tiene una función clara
2. **Flexibilidad**: Añadir nuevos MCP servers sin tocar código
3. **Configuración centralizada**: Todo configurable desde Filament
4. **Normalización unificada**: Una sola lógica para todos los MCPs
5. **Vista unificada**: Todos los datos normalizados en Filament
6. **Debugging visual**: Workflows visuales muestran flujo completo

---

## Documentos Relacionados

- `docs/NOVA_ARCHITECTURE_MASTER.md` — Arquitectura maestra v2.0
- `docs/guides/flujo-completo-nova-agentic-chatbot.md` — Flujo usuario → Filament
- `docs/plans/nova-agentic-chatbot-integration.md` — Plan de integración
