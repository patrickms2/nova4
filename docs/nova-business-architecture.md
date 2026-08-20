# Nova Business — Arquitectura de modelos y recursos

## Entidad raíz: NovaBusiness

Todo en el sistema orbita alrededor de `NovaBusiness`. Un cliente = un negocio.

```
NovaBusiness
│   name, slug, business_type, status
│   contact_name/email/phone, website_url
│   subscription_amount, commission_rate, settings
│
├── 📦 NovaService (1..N)           hasMany
│   │   name, code, service_type
│   │   has_whatsapp, has_mcp, has_sales, has_development, has_maintenance
│   │   monthly_amount, commission_rate
│   │
│   ├── NovaWhatsappChannel         hasMany via service
│   ├── NovaAiProfile               hasMany via service
│   └── NovaRequest                 hasMany via service
│
├── 🔌 Server (MCP Servers) (1..N)  hasMany [PENDIENTE: añadir nova_business_id]
│   │   name, slug, endpoint, transport, instructions
│   │   auth_type, credentials, status
│   │   last_checked_at, last_error
│   │
│   ├── Tool (1..N)                 hasMany
│   │   │   name, description, input_schema
│   │   │   handler_code (ejecutable)
│   │   └── [listing_tool en Prompt metadata]
│   │
│   ├── Resource (1..N)             hasMany
│   │   │   name, uri, mime_type, content
│   │   └── handler_code (dinámico)
│   │
│   └── Prompt (1..N)               hasMany
│       │   name, content
│       └── metadata:
│               listing_tool   → nombre del Tool para listings
│               listing_intro  → texto de intro
│               listing_cta    → texto de CTA
│               listing_params → JSON de params extra
│
├── 🧠 NovaAiKnowledge (1..N)       hasMany
│   │   title, content, status
│   │   embedding, vectorized_at
│   └── metadata: source, domain, imported_at
│
├── 👤 NovaExternalCustomer (1..N)  hasMany
│   │   source (sirvo|latepoint|magento|taxilanz)
│   │   external_id, name, email, phone
│   └── [pivot con bookings, orders, transactions]
│
├── 📅 NovaExternalBooking (1..N)   hasMany
│   │   source, external_id, intent_key
│   │   service_name, booking_date, booking_time
│   │   attendees, adults, children
│   │   total, currency
│   │   booking_status, payment_status
│   │   confirmation_code, admin_url
│   └── → NovaExternalCustomer, NovaExternalCatalogItem
│
├── 🛒 NovaExternalOrder (1..N)     hasMany
│   │   source (magento|woocommerce|shopify)
│   │   external_id, status, payment_status
│   │   subtotal, tax, shipping, discount, grand_total
│   │   items (JSON), payment_method
│   └── → NovaExternalCustomer
│
├── 💳 NovaExternalTransaction (1..N) hasMany
│   │   source, external_id
│   │   amount, currency, status
│   └── → NovaExternalOrder, NovaExternalBooking
│
├── 📦 NovaExternalCatalogItem (1..N) hasMany
│   │   source (sirvo|latepoint|magento|woo)
│   │   external_id, name, description
│   │   price, currency, category
│   └── metadata: stock, sku, images
│
├── ⚙️ NovaIntegrationSetting (1..N) hasMany
│   │   integration_type (sirvo|latepoint|magento|taxilanz|wa)
│   │   status, credentials (encrypted)
│   └── settings: endpoint, api_key, webhook_url
│
├── 🔄 NovaIntegrationSyncLog (1..N) hasMany
│   │   integration_type, entity_type
│   │   records_synced, errors
│   └── synced_at
│
├── 📱 NovaWhatsappChannel (1..N)   hasMany (también via NovaService)
│   │   phone_number, status
│   └── NovaWhatsappMessage (1..N)
│       │   direction (in|out), body
│       │   intent, stage, raw_payload
│       └── sent_at, read_at
│
├── 🤖 NovaAiProfile (1..N)         hasMany
│   │   name, system_prompt, model
│   │   temperature, max_tokens
│   └── status
│
├── 📋 NovaRequest (1..N)           hasMany
│   │   type, status, title, summary
│   └── context (JSON)
│
└── 🔍 NovaContext (1..N)           hasMany (histórico de contexto por cliente)
        phone, visited_businesses
        last_intent, preferences
```

---

## Flujo de datos por tipo de negocio

### Restaurante (ej. Sirvo)
```
NovaBusiness (La Geria Restaurant)
├── NovaService [has_whatsapp=true, has_mcp=true]
├── Server → sirvo-restaurantes Tool
│   └── Prompt: use-server → listing_tool=sirvo-restaurantes
├── NovaExternalBooking ← Nova MCP normaliza respuestas Sirvo
├── NovaExternalCatalogItem ← branches/mesas de Sirvo (normalizado)
└── NovaAiKnowledge ← descripción, carta, horarios
```

### Bodega / Tours (ej. La Geria)
```
NovaBusiness (La Geria)
├── NovaService [has_mcp=true, has_services=true]
├── Server → lageria-latepoint-list-services Tool
│   └── Prompt: use-server → listing_tool=lageria-latepoint-list-services
├── NovaExternalBooking ← Nova MCP normaliza respuestas LatePoint
├── NovaExternalCatalogItem ← visitas/tours (Visita ES, EN, DE, Cata)
└── NovaAiKnowledge ← historia bodega, vinos, precios
```

### Taxi Hotels (ej. Taxilanz)
```
NovaBusiness (Taxilanz)
├── NovaService [has_mcp=true, has_maintenance=true]
├── Server → hotel_list Tool
│   └── Prompt: use-server → listing_tool=hotel_list
├── NovaExternalBooking ← Nova MCP normaliza respuestas taxi
├── NovaExternalCustomer ← huéspedes (Cliente 531, etc.)
└── NovaAiKnowledge ← zonas, municipios, tipos taxi
```

### eCommerce (ej. Lanzaloe)
```
NovaBusiness (Lanzaloe)
├── NovaService [has_sales=true, has_mcp=true]
├── Server → lanzaloe-magento Tool
├── NovaExternalOrder ← Nova MCP normaliza respuestas Magento
├── NovaExternalCatalogItem ← productos aloe vera
├── NovaExternalTransaction ← Nova MCP normaliza pagos
└── NovaAiKnowledge ← catálogo, propiedades aloe, envíos
```

---

## Panel Filament por entidad

### Admin Panel (hub técnico)
```
Servers          → gestión técnica de MCP servers
  Tools          → handler_code, schemas
  Resources      → content, URIs
  Prompts        → metadata de configuración
McpLogs          → trazas de llamadas MCP
ExternalSources  → fuentes de sync
```

### App Panel (vista del cliente)
```
NovaBusiness
├── Edit          → datos del cliente
├── Servicios     → NovaService (flags has_*)
├── MCP Servers   → Server filtrado por nova_business_id
│   ├── Tools     → RelationManager
│   ├── Resources → RelationManager
│   └── Prompts   → RelationManager (aquí se edita listing_tool, etc.)
├── Conocimiento IA → NovaAiKnowledge (texto libre + web import)
├── Reservas      → NovaExternalBooking (de cualquier fuente)
├── Pedidos       → NovaExternalOrder
├── Clientes      → NovaExternalCustomer
├── Catálogo      → NovaExternalCatalogItem
├── Integraciones → NovaIntegrationSetting + SyncLog
├── WhatsApp      → NovaWhatsappChannel + mensajes
├── IA Profiles   → NovaAiProfile (prompts del agente)
└── Chats/Requests → NovaRequest
```

---

## Cambios necesarios para llegar aquí

### En base de datos
| Tabla | Cambio |
|---|---|
| `servers` | + `nova_business_id` (nullable FK) |
| `servers` | + `auth_type`, `credentials`, `status`, `last_checked_at`, `last_error` |
| `nova_ai_profiles` | verificar si ya tiene `nova_business_id` directo |
| `nova_requests` | verificar si tiene `nova_business_id` |

### En modelos
| Modelo | Cambio |
|---|---|
| `NovaBusiness` | + `servers()`, `aiProfiles()`, `externalBookings()`, `externalOrders()`, etc. |
| `Server` | + `business()`, `scopeLocal()`, `scopeForNova()` |
| `NovaMcpServer` | → alias de `Server` con global scope |

### En servicios
| Servicio | Cambio |
|---|---|
| `NovaOrchestratorService` | `promptMetaFor()` busca por `business->servers()` |
| `NovaOrchestratorService` | + métodos de normalización (`normalizeBookingResponse()`, etc.) |
| `NovaOrchestratorService` | + registro en `nova_external_bookings/orders/transactions` |
| `NovaKnowledgeService` | acepta `nova_business_id` directo |
| `buildLiveListingReply()` | recibe `NovaBusiness` en lugar de detectar por slug |

### En Filament App
| Recurso | Cambio |
|---|---|
| `NovaBusinessResource` | añadir tabs: MCP Servers, Reservas, Pedidos, Catálogo, Sync |
| `NovaMcpServerResource` | migrar a RelationManager dentro de NovaBusiness |

---

## Estado

- [x] Fase 1: Unificar Server + NovaMcpServer (ver `unify-server-models.md`)
- [ x] Fase 2: Vincular servers hub → businesses (seed)
- [x ] Fase 3: RelationManagers en App panel (Tools, Resources, Prompts por business)
- [x ] Fase 4: `promptMetaFor()` por business en lugar de slug
- [ x] Fase 5: Tab "Reservas/Pedidos/Catálogo" unificado en NovaBusiness
