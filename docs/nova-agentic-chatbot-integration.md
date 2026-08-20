# Nova + Agentic Chatbot Integration Plan

> **Versión**: 1.0
> **Fecha**: 18 Junio 2026
> **Estado**: Planificación

---

## Visión General

Integrar el plugin **Agentic Chatbot** en Nova AI/Chatbots para proporcionar:

1. **Workflows visuales no-code** para diseñar integraciones
2. **Nodos MCP específicos** para conectar con MCP servers
3. **Visualización de dependencias** entre clientes, servicios, MCP servers, bots, WhatsApp
4. **Flexibilidad extrema** en reglas y conocimiento

**Nombre sugerido**: Nova AI (o Nova Chatbots)

---

## Arquitectura Integrada

### Componentes

```
┌─────────────────────────────────────────────────────────────┐
│                     Nova AI (Filament)                      │
├─────────────────────────────────────────────────────────────┤
│  Agentic Chatbot Layer:                                      │
│  - Bots (configuración de asistentes)                        │
│  - Workflows (builder visual)                                │
│  - Knowledge Sources (text, file, URL, API)                 │
│  - Widget (chat embed)                                       │
│  - Channels (Telegram, Slack, WhatsApp)                      │
├─────────────────────────────────────────────────────────────┤
│  Nova MCP Layer:                                              │
│  - Servers (MCP servers con capabilities)                    │
│  - Tools (tools de cada server)                              │
│  - Intent Mapping (intención → server)                       │
│  - Normalizer (normaliza respuestas)                         │
│  - Registration (registra en Filament)                        │
├─────────────────────────────────────────────────────────────┤
│  Nova Business Layer:                                         │
│  - NovaBusiness (clientes)                                    │
│  - NovaService (servicios por cliente)                        │
│  - NovaExternalBooking/Order/Transaction                      │
│  - NovaAiKnowledge (conocimiento)                             │
└─────────────────────────────────────────────────────────────┘
```

### Flujo Integrado

```
Usuario → Agentic Chatbot Widget
            ↓
      Parent Agent (Agentic Chatbot)
            ↓
      Workflow Visual (Agentic Chatbot)
            ↓
      MCP Server Node (nuevo)
            ↓
      Nova MCP Router (Nova)
            ↓
      MCP Externo (Sirvo, LatePoint, etc.)
            ↓
      Nova Normalizer + Registro (Nova)
            ↓
      Workflow Continúa (Agentic Chatbot)
            ↓
      Respuesta al Usuario
```

---

## Nodos MCP para Workflows Visuales

### 1. MCP Server Node

**Propósito**: Conectar con un MCP server específico y ejecutar una tool.

**Configuración**:
- **Server**: Select (servers activos)
- **Tool**: Select (tools del server seleccionado)
- **Input Parameters**: Dynamic form (basado en schema de la tool)
- **Store Result As**: Variable name (para usar en nodos siguientes)

**Ejemplo**:
```
Node: MCP Server
Config:
  - Server: Sirvo MCP
  - Tool: create_reservation
  - Input Parameters:
    - date: {{booking_date}}
    - time: {{booking_time}}
    - guests: {{party_size}}
    - customer_phone: {{user_phone}}
  - Store Result As: sirvo_result
```

**Output**:
- `success`: boolean
- `data`: array (respuesta normalizada por Nova)
- `error`: string (si falló)

### 2. MCP Server List Node

**Propósito**: Listar servers disponibles con sus capabilities.

**Configuración**:
- **Filter by Capability**: Optional (ej: restaurant_booking)
- **Filter by Business**: Optional (NovaBusiness)
- **Store Result As**: Variable name

**Output**:
- `servers`: array de servers con capabilities

### 3. MCP Intent Detection Node

**Propósito**: Detectar intención usando `nova_intent_rules`.

**Configuración**:
- **Message**: Variable name (texto del usuario)
- **Business**: Optional (NovaBusiness)
- **Store Intent As**: Variable name
- **Store Confidence As**: Variable name

**Output**:
- `intent`: string (ej: restaurant_booking)
- `confidence`: float (0-1)
- `matched_keywords`: array

### 4. MCP Routing Node

**Propósito**: Enrutar a diferentes MCP servers basado en intención.

**Configuración**:
- **Intent Variable**: Variable name (del nodo anterior)
- **Business Variable**: Optional (NovaBusiness)
- **Routing Rules**: Array de reglas
  - Intent → Server mapping
  - Priority
  - Conditions

**Output**:
- `selected_server`: Server
- `selected_tool`: Tool
- `routing_decision`: array

### 5. MCP Normalization Node

**Propósito**: Normalizar respuesta de MCP externo a estructura Nova.

**Configuración**:
- **Raw Response**: Variable name (respuesta del MCP)
- **Server**: Variable name (server usado)
- **Response Type**: booking | order | transaction
- **Store Normalized As**: Variable name

**Output**:
- `normalized`: array (estructura Nova)
- `source`: string (nombre del server)

### 6. MCP Registration Node

**Propósito**: Registrar datos normalizados en tablas Nova.

**Configuración**:
- **Normalized Data**: Variable name (del nodo anterior)
- **Server**: Variable name
- **Intent**: Variable name
- **Record Type**: booking | order | transaction
- **Store Record ID As**: Variable name

**Output**:
- `record_id`: int (ID del registro creado)
- `success`: boolean

### 7. MCP Knowledge Search Node

**Propósito**: Buscar en `NovaAiKnowledge` usando embeddings.

**Configuración**:
- **Query**: Variable name (texto de búsqueda)
- **Business**: Optional (NovaBusiness)
- **Limit**: Number (default: 5)
- **Store Results As**: Variable name

**Output**:
- `results`: array de knowledge chunks
- `citations`: array

### 8. MCP Context Node

**Propósito**: Obtener contexto conversacional de un usuario.

**Configuración**:
- **User Phone**: Variable name
- **Store Context As**: Variable name

**Output**:
- `context`: array (visitas previas, preferencias, etc.)

### 9. MCP Cross-Selling Node

**Propósito**: Obtener sugerencias de cross-selling basadas en intención.

**Configuración**:
- **Intent**: Variable name
- **Business**: Optional (NovaBusiness)
- **Store Suggestions As**: Variable name

**Output**:
- `suggestions`: array de servicios sugeridos

---

## Ejemplo de Workflow Visual

### Workflow: Reserva de Restaurante

```
Start
  ↓
[Collect Input] → booking_date, booking_time, party_size
  ↓
[MCP Intent Detection] → intent: restaurant_booking
  ↓
[MCP Routing] → Server: Sirvo MCP, Tool: create_reservation
  ↓
[MCP Server] → Ejecutar create_reservation
  ↓
[MCP Normalization] → Normalizar respuesta Sirvo
  ↓
[MCP Registration] → Registrar en nova_external_bookings
  ↓
[Condition] → success?
  ├─ Yes → [Send Message] → "✅ Reserva confirmada"
  └─ No → [Send Message] → "❌ Error: {{error}}"
  ↓
[MCP Cross-Selling] → Sugerir visitas bodega
  ↓
End
```

### Workflow: Compra eCommerce

```
Start
  ↓
[Collect Input] → product_id, quantity, customer_data
  ↓
[MCP Intent Detection] → intent: product_purchase
  ↓
[MCP Routing] → Server: Lanzaloe MCP, Tool: create_order
  ↓
[MCP Server] → Ejecutar create_order
  ↓
[MCP Normalization] → Normalizar respuesta Magento
  ↓
[MCP Registration] → Registrar en nova_external_orders
  ↓
[MCP Registration] → Registrar transaction en nova_external_transactions
  ↓
[Condition] → payment_success?
  ├─ Yes → [Send Message] → "✅ Pedido confirmado"
  └─ No → [Send Message] → "❌ Error en pago"
  ↓
End
```

---

## Beneficios de la Integración

### 1. Visualización de Dependencias

**Antes**: Código hard-coded, difícil de ver dependencias.

**Ahora**: Workflow visual donde se ve claramente:
- Cliente → Servicios → MCP Servers → Tools
- Flujo de datos entre nodos
- Puntos de decisión y branching
- Manejo de errores

### 2. Flexibilidad Extrema

**Antes**: Cambiar routing = modificar código + desplegar.

**Ahora**: Cambiar routing = arrastrar nodo en workflow visual.

**Antes**: Añadir nueva intención = modificar código.

**Ahora**: Añadir nueva intención = añadir nodo MCP Intent Detection.

### 3. Diseño No-Code

**Administradores pueden**:
- Diseñar workflows completos sin tocar código
- Conectar MCP servers visualmente
- Configurar normalización y registro visualmente
- Probar workflows desde Filament

### 4. Composición de Servicios

**Posible crear workflows complejos**:
- Reserva restaurante + visita bodega + taxi
- Compra producto + envío + seguimiento
- Multi-step onboarding con validaciones

### 5. Debugging Visual

**Ver exactamente**:
- Qué nodo falló
- Qué datos pasaron entre nodos
- Dónde se tomó cada decisión
- Estado de cada MCP server

---

## Plan de Implementación

### Fase 1: Instalación y Configuración (1 día)

- [ x] Instalar Agentic Chatbot plugin
- [x ] Configurar base de datos (tablas del plugin)
- [x ] Configurar Filament panel
- [x ] Crear primer bot de prueba

### Fase 2: Nodos MCP Personalizados (3-4 días)

- [x ] Crear nodo MCP Server
- [ x] Crear nodo MCP Server List
- [ x] Crear nodo MCP Intent Detection
- [x ] Crear nodo MCP Routing
- [x ] Crear nodo MCP Normalization
- [x ] Crear nodo MCP Registration
- [x ] Crear nodo MCP Knowledge Search
- [x ] Crear nodo MCP Context
- [ x] Crear nodo MCP Cross-Selling

### Fase 3: Integración con Nova MCP (2-3 días)

- [x ] Conectar nodos MCP con NovaOrchestratorService
- [x ] Integrar normalización en nodos
- [x ] Integrar registro en nodos
- [ x] Probar workflows con MCP reales

### Fase 4: Workflows de Ejemplo (2 días)

- [x ] Workflow: Reserva restaurante
- [x ] Workflow: Visita bodega
- [x ] Workflow: Compra eCommerce
- [x ] Workflow: Taxi booking
- [x ] Workflow: Multi-servicio (restaurante + bodega + taxi)

### Fase 5: Documentación y Training (1 día)

- [ ] Documentar nodos MCP
- [ ] Crear guías de workflows
- [ ] Training para administradores
- [ ] Video demo

**Total**: 9-11 días

---

## Technical Considerations

### 1. Extensión de Agentic Chatbot

Los nodos MCP se implementarán como **custom nodes** extendiendo el sistema de workflows de Agentic Chatbot:

```php
// app/Filament/AgenticChatbot/Nodes/McpServerNode.php
class McpServerNode extends WorkflowNode
{
    public static function type(): string
    {
        return 'mcp_server';
    }

    public function execute(array $context): array
    {
        $server = $this->getConfig('server');
        $tool = $this->getConfig('tool');
        $input = $this->resolveVariables($this->getConfig('input'));

        $result = app(NovaMcpClient::class)
            ->setServer($server)
            ->executeTool($tool, $input);

        return [
            'success' => $result['success'],
            'data' => $result['data'],
            'error' => $result['error'] ?? null,
        ];
    }
}
```

### 2. Integración con NovaOrchestratorService

Los nodos MCP usarán los servicios existentes de Nova:

```php
// En nodo MCP Normalization
$normalized = app(NovaResponseNormalizer::class)
    ->normalizeBookingResponse($rawResponse, $server);

// En nodo MCP Registration
$record = app(NovaRegistrationService::class)
    ->registerBooking($normalized, $server, $intent);
```

### 3. Variables y Contexto

Los workflows de Agentic Chatbot ya soportan variables y paso de contexto entre nodos. Los nodos MCP se integrarán naturalmente con este sistema.

### 4. Error Handling

Los nodos MCP incluirán manejo de errores robusto:
- Try-catch en cada nodo
- Mensajes de error claros
- Opción de continuar o detener workflow en error

---

## Estado

- [ ] Fase 1: Instalación y Configuración
- [ ] Fase 2: Nodos MCP Personalizados
- [ ] Fase 3: Integración con Nova MCP
- [ ] Fase 4: Workflows de Ejemplo
- [ ] Fase 5: Documentación y Training

---

## Documentos Relacionados

- `docs/NOVA_ARCHITECTURE_MASTER.md` — Arquitectura maestra Nova
- `docs/plans/nova-implementation-roadmap.md` — Roadmap de implementación Nova
- `docs/MCP/agentic-chatbot-filament-docs-main/` — Documentación Agentic Chatbot
