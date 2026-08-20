# Nova — Arquitectura Maestra

> **Versión**: 2.0
> **Fecha**: 18 Junio 2026
> **Estado**: Arquitectura definida, integración con Agentic Chatbot planificada

---

## Resumen Ejecutivo

Nova es un sistema de orquestación de IA conversacional que actúa como router y normalizador central entre chatbots y múltiples servicios externos (MCP servers), integrado con **Agentic Chatbot** para workflows visuales no-code.

**Arquitectura clave:**
- **Nova AI (Agentic Chatbot Layer)**: Workflows visuales, bots, knowledge sources, widget, channels
- **Nova MCP**: Router inteligente + normalizador central + registro en Filament
- **Agentes IA (MCP Servers)**: Servicios externos que ejecutan acciones específicas
- **Filament**: Panel de administración 100% configurable

**Principio fundamental**: Nova MCP es el único componente que conoce la estructura de datos de Nova. Los MCP externos solo ejecutan acciones; Nova MCP normaliza las respuestas y las registra. Agentic Chatbot proporciona la capa visual no-code para diseñar workflows complejos.

---

## Arquitectura Diferenciada

### 1. Nova AI (Agentic Chatbot Layer)

**Responsabilidad**: Proporcionar workflows visuales no-code, bots configurables, knowledge sources, y canales de entrega.

**Componentes**:
- **Bots**: Asistentes configurables con prompts, modelos, retrieval settings
- **Workflows**: Builder visual con triggers, conditions, AI agents, actions, HTTP requests
- **Knowledge Sources**: Text, file, URL, JSON API con queue-driven ingestion
- **Widget**: Chat embed para websites
- **Channels**: Telegram, Slack, WhatsApp (integrados)
- **Parent Agent**: Runtime que orquesta memoria, knowledge search, workflow execution

**Nodos MCP Personalizados** (nuevos):
- MCP Server Node: Ejecutar tool en MCP server
- MCP Server List Node: Listar servers disponibles
- MCP Intent Detection Node: Detectar intención
- MCP Routing Node: Enrutar a MCP server
- MCP Normalization Node: Normalizar respuesta
- MCP Registration Node: Registrar en Filament
- MCP Knowledge Search Node: Buscar en NovaAiKnowledge
- MCP Context Node: Obtener contexto conversacional
- MCP Cross-Selling Node: Obtener sugerencias

**Beneficios**:
- Diseño visual de integraciones sin código
- Visualización clara de dependencias (cliente → servicios → MCP servers)
- Flexibilidad extrema en reglas y workflows
- Debugging visual de flujos complejos

### 2. Chatbot (Interfaz Conversacional)

**Responsabilidad**: Interactuar con el usuario, capturar información, presentar respuestas.

**Canales**:
- WhatsApp Cloud API (via Agentic Chatbot channels)
- Widget web (via Agentic Chatbot widget)
- Telegram (via Agentic Chatbot channels)
- Slack (via Agentic Chatbot channels)
- Demo artisan
- ServerChat Filament

**No hace**:
- No ejecuta acciones operativas directamente
- No conecta con sistemas externos directamente
- No tiene lógica de negocio específica

### 3. Agentes IA (MCP Servers con Capacidades)

**Responsabilidad**: Ejecutar acciones operativas específicas.

**Ejemplos**:
- **Sirvo MCP**: Reservas de restaurantes, disponibilidad, capacidad
- **La Geria MCP**: Visitas a bodega, wine tours, disponibilidad
- **Taxilanz MCP**: Rutas taxi, excursiones, tarifas, reservas
- **Lanzaloe MCP**: Productos Magento, visitas Laravel, códigos
- **Hotel MCP**: Códigos, descuentos, atribución

**Cada agente**:
- Declara sus capabilities (qué intenciones puede manejar)
- Expone tools específicas
- Tiene knowledge asociado
- Se configura desde Filament

**Importante**: Los MCP externos NO necesitan conocer la estructura de datos de Nova. Solo ejecutan acciones.

### 4. Nova MCP (Router + Normalizador Central)

**Responsabilidad**: Orquestar flujo, enrutar a MCP externos, normalizar respuestas, registrar en Filament.

**Funciones**:
1. **Detectar intención**: Usar `nova_intent_rules` (editables en Filament)
2. **Consultar knowledge**: Recuperar información relevante de `nova_ai_knowledge`
3. **Seleccionar agente**: Basado en intención + capabilities del server
4. **Enrutar petición**: Enviar al MCP server correcto con los datos necesarios
5. **Normalizar respuesta**: Convertir respuesta del MCP externo a estructura de Nova
6. **Registrar en Filament**: Guardar bookings/orders/transactions en tablas normalizadas
7. **Formatear respuesta**: Adaptar la respuesta normalizada al formato del chatbot
8. **Gestionar contexto**: Mantener estado conversacional
9. **Cross-selling**: Sugerir otros servicios basándose en reglas Filament

**Todo configurable desde Filament**:
- Reglas de detección de intención
- Mapeo intención → MCP server
- Capabilities de cada server
- Knowledge por negocio
- Reglas de cross-selling
- Reglas de normalización por tipo de MCP

---

## Flujo Completo

### Ejemplo: Reserva de restaurante (con Agentic Chatbot)

```
1. Usuario: "Quiero reservar mesa mañana a las 20 para 4"
   ↓
2. Agentic Chatbot Widget recibe mensaje
   ↓
3. Parent Agent (Agentic Chatbot) inicia workflow
   ↓
4. Workflow Visual (Agentic Chatbot):
   - [Collect Input] → booking_date, booking_time, party_size
   - [MCP Intent Detection] → intent: restaurant_booking
   - [MCP Routing] → Server: Sirvo MCP, Tool: create_reservation
   - [MCP Server] → Ejecutar create_reservation
   - [MCP Normalization] → Normalizar respuesta Sirvo
   - [MCP Registration] → Registrar en nova_external_bookings
   - [Condition] → success?
   - [MCP Cross-Selling] → Sugerir visitas bodega
   ↓
5. Nova MCP Router (Nova):
   - Selecciona Sirvo MCP basado en mapeo
   - Enruta petición con parámetros
   ↓
6. Sirvo MCP (Agente IA):
   - Ejecuta create_reservation
   - API Sirvo → Crear reserva
   ↓
7. Nova MCP Normalizer (Nova):
   - Convierte estructura Sirvo → estructura Nova
   ↓
8. Nova MCP Registration (Nova):
   - Crea registro en nova_external_bookings
   ↓
9. Workflow continúa (Agentic Chatbot):
   - Evalúa condición success
   - Genera respuesta con cross-selling
   ↓
10. Agentic Chatbot Widget presenta respuesta
    WhatsApp: "✅ Reserva confirmada para mañana a las 20:00 para 4 personas.
    ¿Te gustaría visitar nuestra bodega durante tu estancia?"
```

### Ejemplo: Reserva de restaurante (sin Agentic Chatbot - legacy)

```
1. Usuario: "Quiero reservar mesa mañana a las 20 para 4"
   ↓
2. Chatbot recibe y reenvía a Nova MCP
   POST /api/nova/chat
   ↓
3. Nova MCP detecta intención
   Intent: "restaurant_booking" (usando nova_intent_rules)
   ↓
4. Nova MCP selecciona agente IA
   Mapeo: restaurant_booking → Sirvo MCP (usando nova_intent_to_server_mapping)
   ↓
5. Nova MCP enruta al agente
   NovaMcpClient → Sirvo MCP → create_reservation
   ↓
6. Agente IA ejecuta acción
   Sirvo MCP → API Sirvo → Crear reserva
   ↓
7. Nova MCP normaliza respuesta
   NovaResponseNormalizer → Convierte estructura Sirvo → estructura Nova
   ↓
8. Nova MCP registra en Filament
   NovaRegistrationService → Crea registro en nova_external_bookings
   ↓
9. Nova MCP formatea respuesta
   Adaptar respuesta normalizada al formato del chatbot
   ↓
10. Chatbot presenta respuesta
    WhatsApp: "✅ Reserva confirmada para mañana a las 20:00 para 4 personas."
```

---

## Modelo de Datos

### Entidad Raíz: NovaBusiness

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
├── 🔌 Server (MCP Servers) (1..N)  hasMany
│   │   name, slug, endpoint, transport, instructions
│   │   auth_type, credentials, status, capabilities
│   │   last_checked_at, last_error
│   │
│   ├── Tool (1..N)                 hasMany
│   ├── Resource (1..N)             hasMany
│   └── Prompt (1..N)               hasMany
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
├── 📱 NovaWhatsappChannel (1..N)   hasMany
│   │   phone_number, status
│   └── NovaWhatsappMessage (1..N)
│
├── 🤖 NovaAiProfile (1..N)         hasMany
│   │   name, system_prompt, model
│   │   temperature, max_tokens
│   └── status
│
└── 📋 NovaRequest (1..N)           hasMany
    │   type, status, title, summary
    └── context (JSON)
```

---

## Tablas Clave para Routing y Normalización

### 1. `nova_intent_to_server_mapping`

Mapeo de intenciones a MCP servers.

```sql
CREATE TABLE nova_intent_to_server_mapping (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    nova_business_id BIGINT NULL, -- null = regla global
    intent_key VARCHAR(50) NOT NULL, -- restaurant_booking, winery_visit, taxi_booking
    server_id BIGINT NOT NULL, -- FK servers
    tool_id BIGINT NULL, -- FK tools (opcional, si requiere tool específica)
    priority INT DEFAULT 0,
    conditions JSON NULL, -- condiciones adicionales
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Uso**: Nova MCP usa esta tabla para seleccionar qué MCP server manejará cada intención.

### 2. `nova_intent_rules`

Reglas de detección de intención (ya existe).

```sql
CREATE TABLE nova_intent_rules (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    nova_business_id BIGINT NULL,
    intent_key VARCHAR(50) NOT NULL,
    keywords JSON NOT NULL,
    rule_type ENUM('include', 'exclude', 'system_topic') NOT NULL,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Uso**: Nova MCP usa esta tabla para detectar la intención del usuario basándose en keywords.

### 3. `nova_external_bookings`

Reservas normalizadas de todas las fuentes.

```sql
CREATE TABLE nova_external_bookings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    source VARCHAR(50) NOT NULL, -- sirvo, latepoint, taxilanz, etc.
    external_id VARCHAR(255) NOT NULL,
    intent_key VARCHAR(50) NOT NULL,
    service_name VARCHAR(255),
    booking_date DATE,
    booking_time TIME,
    attendees INT,
    adults INT,
    children INT,
    total DECIMAL(10,2),
    currency VARCHAR(3),
    booking_status VARCHAR(50),
    payment_status VARCHAR(50),
    confirmation_code VARCHAR(100),
    admin_url TEXT,
    metadata JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Uso**: Nova MCP registra aquí todas las reservas normalizadas de cualquier MCP externo.

### 4. `nova_external_orders`

Pedidos normalizados de todas las fuentes.

```sql
CREATE TABLE nova_external_orders (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    source VARCHAR(50) NOT NULL, -- magento, woocommerce, shopify
    external_id VARCHAR(255) NOT NULL,
    status VARCHAR(50),
    payment_status VARCHAR(50),
    subtotal DECIMAL(10,2),
    tax DECIMAL(10,2),
    shipping DECIMAL(10,2),
    discount DECIMAL(10,2),
    grand_total DECIMAL(10,2),
    items JSON,
    payment_method VARCHAR(100),
    metadata JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Uso**: Nova MCP registra aquí todos los pedidos normalizados de cualquier MCP externo.

### 5. `nova_external_transactions`

Transacciones/pagos normalizados de todas las fuentes.

```sql
CREATE TABLE nova_external_transactions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    source VARCHAR(50) NOT NULL,
    external_id VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2),
    currency VARCHAR(3),
    status VARCHAR(50),
    metadata JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Uso**: Nova MCP registra aquí todas las transacciones normalizadas de cualquier MCP externo.

---

## Servicios Clave

### 1. NovaOrchestratorService

**Responsabilidad**: Orquestar el flujo completo de una conversación.

**Métodos clave**:
- `getServerForIntent(string $intentKey, ?NovaBusiness $business)`: Selecciona MCP server basado en mapeos Filament
- `normalizeBookingResponse(array $response, Server $server): array`: Normaliza respuesta de booking
- `normalizeOrderResponse(array $response, Server $server): array`: Normaliza respuesta de order
- `registerBooking(array $normalized, Server $server, string $intent)`: Registra booking en Filament

### 2. NovaResponseNormalizer

**Responsabilidad**: Normalizar respuestas de MCP externos a estructura de Nova.

**Métodos**:
- `normalizeBookingResponse(array $response, Server $server): array`
- `normalizeOrderResponse(array $response, Server $server): array`
- `normalizeTransactionResponse(array $response, Server $server): array`

**Estrategia**: Cada tipo de MCP (Sirvo, LatePoint, Magento, WooCommerce) tiene su propia lógica de normalización.

### 3. NovaRegistrationService

**Responsabilidad**: Registrar datos normalizados en tablas de Nova.

**Métodos**:
- `registerBooking(array $normalized, Server $server, string $intent): NovaExternalBooking`
- `registerOrder(array $normalized, Server $server): NovaExternalOrder`
- `registerTransaction(array $normalized, Server $server): NovaExternalTransaction`

---

## Configuración desde Filament

### 1. Server Resource

**Pestaña Capabilities**:
- Capabilities: TagsInput
  - restaurant_booking
  - availability_check
  - menu_info
  - winery_visit
  - taxi_booking
  - product_purchase
  - etc.

**Pestaña Tools**:
- Lista de tools del server
- Cada tool tiene metadata sobre qué parámetros requiere

### 2. NovaIntentToServerMapping Resource

**Campos**:
- Intent key: Select (enum de intenciones conocidas)
- Server: Select (servers activos)
- Tool: Select (opcional, tools del server)
- Priority: Number
- Conditions: KeyValue
- Business: Select (opcional, para reglas específicas por negocio)
- Is active: Toggle

**Ejemplos de mapeo**:
```
restaurant_booking → Sirvo MCP → create_reservation
winery_visit → La Geria MCP → book_visit
taxi_booking → Taxilanz MCP → create_route
product_purchase → Lanzaloe MCP → create_order
```

### 3. NovaIntentRule Resource

**Ampliar con más intenciones**:
- sales_purchase
- route_recommendation
- cancellation_request
- physical_store_visit

### 4. NovaBusiness Resource

**Pestaña Routing**:
- Mostrar qué mapeos de intención→server aplican a este negocio
- Permitir overrides específicos

---

## Integración de Nuevos Servicios

### Para añadir un nuevo servicio (ej. alquiler de villas):

1. **Crear el MCP server externo** para alquiler de villas
   - Exponer tools: `list_villas`, `check_availability`, `create_booking`
   - Definir capabilities: `["villa_booking", "availability_check", "villa_info"]`

2. **Registrar el server en MCP Studio:**
   - Crear registro en tabla `servers` con endpoint del MCP de villas
   - Configurar `capabilities` declarativas
   - Añadir tools del server

3. **Configurar routing desde Filament:**
   - Crear mapeo en `nova_intent_to_server_mapping`:
     - Intent: `villa_booking` → Server: Villas MCP
   - Opcional: añadir reglas específicas por negocio

4. **Añadir intenciones (si no existen):**
   - Crear regla en `nova_intent_rules` para detectar "villa", "alquiler", "vacaciones"

5. **Añadir normalización (si es necesario):**
   - Añadir lógica en `NovaResponseNormalizer` para normalizar respuestas del MCP de villas

**Tiempo estimado**: 5-10 minutos para servicios estándar.

---

## Beneficios

1. **Separación clara**: Chatbot (interfaz) vs Agentes IA (capacidades)
2. **Configuración 100% Filament**: Sin tocar código para añadir nuevos servers o intenciones
3. **Escalabilidad**: Añadir un nuevo MCP server = crear mapeo en Filament
4. **Flexibilidad**: Cambiar routing sin desplegar código
5. **Trazabilidad**: Cada mapeo está registrado en DB
6. **Testing**: Facilita testing de diferentes rutas de routing
7. **Normalización central**: Nova MCP normaliza todas las respuestas, MCP externos no necesitan conocer estructura de Nova
8. **Integración rápida**: Cualquier MCP externo puede integrarse en minutos sin modificar su código
9. **Workflows visuales no-code**: Diseño de integraciones complejas sin programar
10. **Visualización de dependencias**: Ver claramente cliente → servicios → MCP servers
11. **Debugging visual**: Identificar fallos en workflows visualmente
12. **Canales múltiples**: WhatsApp, Telegram, Slack, Widget integrados

---

## Estado de Implementación

### Completado
- [x] Unificación de modelos `Server` y `NovaMcpServer`
- [x] Tabla `nova_intent_to_server_mapping` creada
- [x] Modelo `NovaIntentToServerMapping` con relaciones y scopes
- [x] Filament Resource `NovaIntentToServerMappingResource`
- [x] Seeder `NovaIntentToServerMappingSeeder` con mapeos iniciales
- [x] Método `getServerForIntent()` en `NovaOrchestratorService`
- [x] Arquitectura documentada
- [x] Plan de integración con Agentic Chatbot

### Pendiente (Nova Core)
- [x ] Servicio de normalización `NovaResponseNormalizer`
- [x ] Servicio de registro `NovaRegistrationService`
- [x ] Integración de normalización en `NovaOrchestratorService`
- [x ] Filament Resources para `nova_external_bookings/orders/transactions`
- [ ] Tests para routing dinámico
- [x ] Ampliar intenciones en `nova_intent_rules`

### Pendiente (Integración Agentic Chatbot)
- [x ] Instalación y configuración de Agentic Chatbot
- [x ] Nodos MCP personalizados (9 nodos)
- [x ] Integración de nodos con NovaOrchestratorService
- [x ] Workflows de ejemplo (5 workflows)
- [ ] Documentación y training

---

## Documentos Relacionados

- `docs/05-nova-ai-mcp-architecture.md` — Arquitectura detallada de Nova AI + MCP
- `docs/plans/nova-chatbot-vs-agents-architecture.md` — Diferenciación chatbot vs agentes
- `docs/plans/nova-business-architecture.md` — Modelo de datos de NovaBusiness
- `docs/plans/nova-filament-driven-architecture.md` — Arquitectura Filament-driven
- `docs/superpowers/plans/2026-05-23-server-external-sync.md` — Plan de normalización y registro
- `docs/plans/nova-agentic-chatbot-integration.md` — Plan de integración con Agentic Chatbot
- `docs/plans/nova-implementation-roadmap.md` — Roadmap de implementación
- `docs/MCP/agentic-chatbot-filament-docs-main/` — Documentación Agentic Chatbot
