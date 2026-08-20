# Nova — Arquitectura dirigida por Filament

## Problema actual

Todo está hard-coded en PHP:

| Problema | Dónde | Impacto |
|---|---|---|
| 65 `str_contains` para detectar intents | `NovaOrchestratorService` | Añadir un cliente = tocar código |
| Reglas de cross-selling | `NovaCrossSellingService` | No editable desde Filament |
| Keywords de intent | `NovaConversationDataExtractor` | Igual |
| Tool names para listings | Prompt metadata (hacky) | Difícil de descubrir |
| `NovaRequest` sin `nova_business_id` | tabla `nova_requests` | Sin contexto de cliente |
| Semillas de datos en comandos Artisan | `NovaSeedLocalMcpHub` | No regenerable desde UI |
| Plantillas de agentes hardcoded | Varios services | No personalizables por cliente |

---

## Arquitectura objetivo

```
NovaBusiness  (raíz de todo)
│
├── [CONFIG] business_type → activa plantilla de comportamiento
│
├── 📋 NovaListingCategory (nueva tabla)
│   ← reemplaza isHotel/isVisit/isRestaurant hard-coded
│   │   nova_business_id, server_id, tool_id
│   │   slug (hotel|restaurant|visit|route|product)
│   │   keywords (JSON array) ← detección de intent
│   │   intro_text, cta_text
│   │   count_label (hoteles|restaurantes|visitas)
│   └── is_active
│
├── 🎯 NovaIntentRule (nueva tabla)
│   ← reemplaza NovaConversationDataExtractor keywords hardcoded
│   │   nova_business_id (nullable = global)
│   │   intent_key (restaurant_booking|winery_visit|hotel|etc.)
│   │   keywords (JSON array)
│   │   system_topics (JSON) ← excluidos de system_info
│   └── priority
│
├── 🔄 NovaCrossSellingRule (nueva tabla)
│   ← reemplaza NovaCrossSellingService hardcoded
│   │   from_business_id → to_business_id
│   │   trigger_intent (winery_visit|restaurant_booking|etc.)
│   │   message
│   │   cta_url (opcional)
│   └── priority, is_active
│
├── 🤖 NovaAiProfile (ya existe — ampliar)
│   │   nova_business_id, nova_service_id
│   │   name, provider, model, system_prompt
│   │   temperature, max_tokens, tools_policy
│   └── settings:
│           fallback_message    ← cuando no hay datos live
│           location_stop_words ← para filtro geolocalización
│           system_name_aliases ← para no filtrar como ubicación
│
├── 🔌 Server (ampliar — ver unify-server-models.md)
│   │   + nova_business_id
│   │   + auth_type, credentials, status
│   │   + last_checked_at, last_error
│   │
│   ├── Tool
│   │   └── [metadata] input_example, output_example
│   ├── Resource
│   └── Prompt
│       └── metadata (ya existe — estandarizar keys)
│
├── 📊 NovaIntegrationSetting (ya existe — ampliar UI)
│   │   source_type (sirvo|latepoint|magento|taxilanz|woocommerce)
│   │   auth_type, credentials, base_url
│   └── settings: sync_interval, webhook_secret, field_mappings
│
├── 📅 NovaExternalBooking (ya existe)
├── 🛒 NovaExternalOrder (ya existe)
├── 💳 NovaExternalTransaction (ya existe)
├── 📦 NovaExternalCatalogItem (ya existe)
├── 👤 NovaExternalCustomer (ya existe)
├── 📱 NovaWhatsappChannel (ya existe)
├── 🧠 NovaAiKnowledge (ya existe)
│
└── 📋 NovaRequest (añadir nova_business_id)
    │   type, status, title, summary
    └── context (JSON)
```

---

## Nuevas tablas necesarias

### `nova_listing_categories`
Reemplaza `isHotel/isVisit/isRestaurant + promptMetaFor()` hardcoded.

```sql
id
nova_business_id  FK nova_businesses
server_id         FK servers (nullable)
tool_id           FK tools (nullable)
slug              VARCHAR  -- hotel | restaurant | visit | route | product
keywords          JSON     -- ["hotel","hoteles","alojamiento"]
intro_text        TEXT     -- "Hoteles activos en Taxilanz:"
cta_text          TEXT     -- "¿En cuál solicito taxi?"
count_label       VARCHAR  -- "hoteles"
is_active         BOOLEAN
sort_order        INT
```

**Filament**: Business → Listing Categories → tabla editable con keywords como tags

### `nova_intent_rules`
Reemplaza keywords hardcoded en `NovaConversationDataExtractor`.

```sql
id
nova_business_id  FK nullable (null = regla global)
intent_key        VARCHAR  -- commercial_info | restaurant_booking | system_info
rule_type         ENUM     -- include | exclude | system_topic
keywords          JSON     -- ["listado","lista","ver","activos"]
priority          INT
is_active         BOOLEAN
```

**Filament**: Admin → Intent Rules → gestión global + por business

### `nova_cross_selling_rules`
Reemplaza `NovaCrossSellingService` hardcoded.

```sql
id
from_business_id  FK nova_businesses
to_business_id    FK nova_businesses
trigger_intent    VARCHAR  -- winery_visit | restaurant_booking
message           TEXT
cta_label         VARCHAR
cta_url           VARCHAR (nullable)
priority          INT
is_active         BOOLEAN
```

**Filament**: Business → Cross-selling → definir qué negocios se promocionan entre sí

---

## Servicios que desaparecen o se simplifican

| Servicio actual | Pasa a ser |
|---|---|
| `NovaCrossSellingService` | query sobre `nova_cross_selling_rules` |
| `NovaConversationDataExtractor::isSystemInfoQuery()` | query sobre `nova_intent_rules` |
| `NovaOrchestratorService::buildLiveListingReply()` | query sobre `nova_listing_categories` |
| `NovaOrchestratorService::promptMetaFor()` | `Server::use_server_prompt->metadata` via relación directa |
| `NovaMcpCreationService` (semillas hardcoded) | plantillas en Filament → generar desde UI |

---

## Plantillas (Templates) por tipo de negocio

Cuando se crea un `NovaBusiness`, el admin elige `business_type` y el sistema propone:

| business_type | Se propone auto-generar |
|---|---|
| `restaurant` | Listing category (restaurant), Intent rules básicas, Prompt use-server para Sirvo |
| `winery_tour` | Listing category (visit), cross-selling con tienda, Prompt LatePoint |
| `taxi_hotel` | Listing category (hotel), Intent rules hotel+taxi, Tool hotel_list |
| `ecommerce` | Listing category (product), sync Magento/WooCommerce, NovaAiProfile ecom |
| `generic` | Solo estructura base |

**En Filament**: botón "Aplicar plantilla" en la vista del business → crea los registros sugeridos como borrador para revisar antes de activar.

---

## Gestión 100% desde Filament

### Admin Panel (hub)
```
Dashboard
├── Servers       → Tools, Resources, Prompts, Logs
├── Intent Rules  → Reglas globales de detección de intent
├── Businesses    → Vista técnica de todos los clientes
└── Templates     → Plantillas por business_type
```

### App Panel (por cliente)
```
NovaBusiness → [tabs]
├── 📋 General          → Edit datos básicos, subscription, comisión
├── 📦 Servicios        → NovaService (flags has_*, monthly_amount)
├── 🔌 MCP Servers      → Server + Tools + Resources + Prompts
│   └── [botón] Generar desde plantilla
├── 🎯 Listing Config   → NovaListingCategory (keywords, intro, cta, tool)
├── 🔄 Cross-selling    → NovaCrossSellingRule
├── 🤖 Agentes IA       → NovaAiProfile (system prompt, model, temp)
├── 🧠 Conocimiento     → NovaAiKnowledge (+ import desde web)
├── ⚙️ Integraciones    → NovaIntegrationSetting + test conexión + sync manual
├── 📅 Reservas         → NovaExternalBooking (filtros, export)
├── 🛒 Pedidos          → NovaExternalOrder
├── 👤 Clientes         → NovaExternalCustomer
├── 📱 WhatsApp         → NovaWhatsappChannel + historial mensajes
└── 📊 Historial        → NovaRequest (conversaciones del agente)
```

---

## Plan de implementación por fases

### Fase 1 — Unificación de modelos
→ ver `unify-server-models.md`

### Fase 2 — Nuevas tablas + migraciones
1. `nova_listing_categories` + seed desde datos actuales de Prompts metadata
2. `nova_intent_rules` + seed desde keywords hardcoded en DataExtractor
3. `nova_cross_selling_rules` + seed desde NovaCrossSellingService
4. Añadir `nova_business_id` a `nova_requests`

### Fase 3 — Filament Resources nuevos
1. `NovaListingCategoryResource` → dentro de NovaBusiness
2. `NovaIntentRuleResource` → Admin Panel (global) + App Panel (por business)
3. `NovaCrossSellingRuleResource` → dentro de NovaBusiness
4. Ampliar tabs en `NovaBusinessResource` (Reservas, Pedidos, Catálogo, WhatsApp)

### Fase 4 — Servicios dirigidos por DB
1. `NovaOrchestratorService::buildLiveListingReply()` → lee `nova_listing_categories`
2. `NovaConversationDataExtractor::isSystemInfoQuery()` → lee `nova_intent_rules`
3. `NovaCrossSellingService::suggestCrossSelling()` → lee `nova_cross_selling_rules`
4. `promptMetaFor()` → eliminar, reemplazar por `Server->listingCategory`

### Fase 5 — Sistema de plantillas
1. Tabla `nova_business_templates` o JSON en config
2. Botón "Aplicar plantilla" en Filament → crea registros en borrador
3. Seed inicial de plantillas desde Artisan (ejecutable una vez)

---

## Estado

- [x ] Fase 1: Unificación Server + NovaMcpServer (ver `unify-server-models.md`)
- [x] Fase 2: Migraciones — `nova_listing_categories`, `nova_cross_selling_rules`, `nova_intent_rules` + `nova_business_id` en `servers` y `nova_requests`
- [x] Fase 2b: Modelos `NovaListingCategory`, `NovaCrossSellingRule`, `NovaIntentRule`
- [x] Fase 2c: Seeders con datos migrados desde código hardcoded (`NovaListingCategoriesSeeder`, `NovaIntentRulesSeeder`)
- [x] Fase 3: Filament Resources — Blueprint-compliant + UX-auditados
  - `NovaListingCategoryResource` — slug como `Select` enum, keywords como `TagsInput`, tool_params como `KeyValue`
  - `NovaCrossSellingRuleResource` — trigger_intent como `Select`, is_active como `ToggleButtons`
  - `NovaIntentRuleResource` — rule_type como `ToggleButtons`, intent_key como `Select`, filtro global vs business
- [~] Fase 4: Servicios dirigidos por DB
  - [~] `buildLiveListingReply()` / listings → existen `NovaListingCategory` y configuración Filament, pero el orquestador conserva lógica y heurísticas legacy.
  - [x] `NovaConversationDataExtractor` → ya lee `nova_intent_rules` para reglas include/system_topic con fallback hardcoded.
  - [x ] `NovaCrossSellingService` → todavía usa reglas hardcoded; la consulta a `nova_cross_selling_rules` existe como intención pero está comentada.
  - [ ] `promptMetaFor()` → todavía no está completamente reemplazado por relación directa `Server`/`NovaListingCategory`.
- [ ] Fase 5: Sistema de plantillas por `business_type`
- [ ] Pendiente: completar seed/activación real por negocio y revisar Taxilanz/listing category `hotel`

## Notas Blueprint

- Leer `docs/filament-blueprint/SKILL.md` + `vendor/filament/blueprint/resources/...` **antes** de crear cualquier Resource
- Enums siempre como `Select` o `ToggleButtons` (≤4 opciones), nunca `TextInput` libre
- Namespaces: layout = `Filament\Schemas\Components\`, fields = `Filament\Forms\Components\`, columns = `Filament\Tables\Columns\`
