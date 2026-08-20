# Nova — Arquitectura Diferenciada: Chatbot vs Agentes IA

## Problema actual

La arquitectura actual mezcla responsabilidades:
- `NovaOrchestratorService` tiene lógica hard-coded para Sirvo, La Geria, Taxilanz
- El routing a MCP servers no es completamente dinámico
- No hay una separación clara entre la interfaz conversacional y las capacidades operativas
- Las reglas de routing no son 100% editables desde Filament

## Arquitectura objetivo

### 1. Chatbot (Interfaz Conversacional)

**Responsabilidad**: Interactuar con el usuario, capturar información, presentar respuestas.

**Canales**:
- WhatsApp Cloud API
- Widget web (/ai-bot)
- Demo artisan
- ServerChat Filament

**No hace**:
- No ejecuta acciones operativas directamente
- No conecta con sistemas externos directamente
- No tiene lógica de negocio específica

**Flujo**:
```
Usuario → Chatbot → Nova MCP (router) → Agente IA (MCP Server) → Sistema externo
```

### 2. Agentes IA (MCP Servers con Capacidades)

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

### 3. Nova MCP (Router + Normalizador Central)

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

## Tablas necesarias

### 1. Ampliar `servers` con capabilities declarativas

```sql
ALTER TABLE servers ADD COLUMN capabilities JSON;
-- capabilities: ["restaurant_booking", "availability_check", "menu_info"]
-- capabilities: ["winery_visit", "tour_booking", "wine_info"]
-- capabilities: ["taxi_booking", "route_info", "fare_quote"]
```

**Filament**: Server → Capabilities (TagsInput editable)

### 2. Tabla `nova_intent_to_server_mapping`

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
    updated_at TIMESTAMP,
    FOREIGN KEY (nova_business_id) REFERENCES nova_businesses(id),
    FOREIGN KEY (server_id) REFERENCES servers(id),
    FOREIGN KEY (tool_id) REFERENCES tools(id)
);
```

**Filament**: Admin → Intent to Server Mapping
- intent_key: Select (enum de intenciones conocidas)
- server_id: Select (servers activos)
- tool_id: Select (opcional, tools del server)
- priority: Number
- conditions: KeyValue (ej: {"min_guests": 2, "requires_payment": true})

### 3. Ampliar `nova_intent_rules` (ya existe)

Ya tiene:
- `intent_key`
- `keywords`
- `rule_type` (include/exclude/system_topic)
- `nova_business_id` (global o por negocio)

**Filament**: Ya existe, verificar que cubre todas las intenciones necesarias.

---

## Flujo completo

### 1. Usuario envía mensaje

```
Usuario: "Quiero reservar mesa mañana a las 20 para 4"
```

### 2. Chatbot recibe y reenvía a Nova MCP

```
POST /api/nova/chat
{
  "message": "Quiero reservar mesa mañana a las 20 para 4",
  "channel": "whatsapp",
  "user": {"phone": "+34600666777"}
}
```

### 3. Nova MCP detecta intención

```php
// NovaOrchestratorService
$intent = $this->dataExtractor->extract($message);
// Result: intent = "restaurant_booking"
```

Usa `nova_intent_rules`:
- Keywords: ["reservar", "mesa", "comer", "restaurante"]
- Intent key: "restaurant_booking"

### 4. Nova MCP selecciona agente IA

```php
// Buscar mapeo intención → server
$mapping = NovaIntentToServerMapping::query()
    ->where('intent_key', 'restaurant_booking')
    ->where('is_active', true)
    ->where(function ($q) use ($business) {
        $q->whereNull('nova_business_id')
          ->orWhere('nova_business_id', $business->id);
    })
    ->orderBy('priority', 'desc')
    ->first();

// Result: server_id = Sirvo MCP
```

Verifica que el server tiene la capability:
```php
$server = Server::find($mapping->server_id);
if (in_array('restaurant_booking', $server->capabilities)) {
    // Usar este server
}
```

### 5. Nova MCP enruta al agente

```php
// NovaMcpClient
$client = new NovaMcpClient($server);
$result = $client->executeTool('create_reservation', [
    'date' => '2026-06-19',
    'time' => '20:00',
    'guests' => 4,
    'customer_phone' => '+34600666777',
]);
```

### 6. Agente IA ejecuta acción

```
Sirvo MCP → API Sirvo → Crear reserva
```

### 7. Nova MCP normaliza respuesta

```php
// NovaOrchestratorService
$normalizedBooking = $this->normalizeBookingResponse($result, $intent, $server);
// Convierte estructura Sirvo → estructura Nova
```

### 8. Nova MCP registra en Filament

```php
// NovaOrchestratorService
NovaExternalBooking::create([
    'source' => $server->name,
    'external_id' => $result['id'],
    'intent_key' => $intent,
    'booking_date' => $normalizedBooking['date'],
    'booking_time' => $normalizedBooking['time'],
    'attendees' => $normalizedBooking['guests'],
    'total' => $normalizedBooking['total'],
    'booking_status' => $normalizedBooking['status'],
    // ... otros campos normalizados
]);
```

### 9. Nova MCP formatea respuesta

```php
// Adaptar respuesta normalizada al formato del chatbot
$reply = $this->formatReply($normalizedBooking, $intent, $channel);
```

### 10. Chatbot presenta respuesta

```
WhatsApp: "✅ Reserva confirmada para mañana a las 20:00 para 4 personas.
Te enviaremos un SMS de confirmación."
```

---

## Configuración desde Filament

### 1. Server Resource (ampliado)

**Pestaña Capabilities**:
- Capabilities: TagsInput
  - restaurant_booking
  - availability_check
  - menu_info
  - etc.

**Pestaña Tools**:
- Lista de tools del server
- Cada tool tiene metadata sobre qué parámetros requiere

### 2. NovaIntentToServerMapping Resource

**Campos**:
- Intent key: Select (enum)
  - commercial_info
  - restaurant_booking
  - winery_visit
  - taxi_booking
  - hotel_booking
  - product_purchase
  - etc.
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
### 3. NovaIntentRule Resource (ya existe)

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

## Implementación por fases

### Fase 1 — Ampliar Server con capabilities
1. Migración para añadir `capabilities` JSON a `servers`
2. Actualizar `Server` model con cast a array
3. Seed inicial de capabilities para servers existentes
4. Filament: añadir campo capabilities en ServerResource

### Fase 2 — Crear tabla de mapeo intención→server
1. Migración `nova_intent_to_server_mapping`
2. Model `NovaIntentToServerMapping`
3. Filament Resource `NovaIntentToServerMappingResource`
4. Seed inicial con mapeos actuales hard-coded

### Fase 3 — Actualizar NovaOrchestratorService
1. Reemplazar lógica hard-coded de selección de server
2. Usar `NovaIntentToServerMapping` para routing dinámico
3. Verificar capabilities del server antes de enrutar
4. Mantener fallback a lógica actual si no hay mapeo

### Fase 4 — Ampliar intenciones
1. Añadir nuevas intenciones a `nova_intent_rules`
2. Actualizar `NovaConversationDataExtractor` para detectarlas
3. Crear mapeos para las nuevas intenciones

### Fase 5 — Documentación y testing
1. Documentar arquitectura diferenciada
2. Crear tests para routing dinámico
3. Verificar que todos los flujos actuales funcionan
4. Añadir ejemplos de configuración en Filament

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

---

## Estado

- [x] Fase 1: Ampliar Server con capabilities — métodos `hasCapability()` y `scopeWithCapability()` añadidos
- [x] Fase 2: Crear tabla de mapeo intención→server — tabla `nova_intent_to_server_mapping` creada
- [x] Fase 3: Actualizar NovaOrchestratorService — método `getServerForIntent()` para routing dinámico
- [x] Fase 4: Ampliar intenciones — añadir nuevas intenciones a `nova_intent_rules`
- [x] Fase 5: Documentación — arquitectura documentada en este archivo
- [ ] Fase 5b: Testing — crear tests para routing dinámico
- [ x] Fase 6: Servicio de normalización — añadir `normalizeBookingResponse()` y similares en NovaOrchestratorService
- [x ] Fase 7: Registro en Filament — integrar creación de `nova_external_bookings/orders/transactions`
