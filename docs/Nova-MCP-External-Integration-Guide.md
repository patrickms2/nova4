# Nova MCP - Guía de Integración para Servidores Externos

## Visión General

Nova MCP es el servidor central de orquestación del ecosistema Nova. Actúa como punto de conexión unificado para integrar sistemas externos mediante el protocolo MCP (Model Context Protocol) estándar.

**Endpoint del servidor Nova:**
```
https://novahubmcp.test/mcp/nova
```

## Capacidades de Integración

### 1. Integración con Plataformas E-commerce

Nova puede conectarse con plataformas de comercio electrónico para:

- **WooCommerce**: Sincronizar productos, pedidos, clientes y stock
- **Magento**: Integración con catálogo, pedidos y gestión de clientes
- **Shopify**: Conexión con productos, órdenes y inventario
- **PrestaShop**: Integración con catálogo y gestión de pedidos

### 2. Integración con Sistemas de Reservas

Conexión con sistemas de gestión de reservas y citas:

- **LatePoint**: Sistema de reservas WordPress
- **Bookly**: Plugin de reservas WordPress
- **Amelia**: Sistema de reservas WordPress
- **Sistemas personalizados**: APIs REST de reservas

### 3. Integración con APIs Laravel

Conexión con aplicaciones Laravel externas:

- APIs REST personalizadas
- Sistemas de gestión interna
- ERPs basados en Laravel
- CRMs Laravel

### 4. Integración con Otros Sistemas

- **WordPress**: Gestión de contenido y WooCommerce
- **Sistemas de pago**: Redsys, Stripe, PayPal
- **CRMs**: HubSpot, Salesforce, Zoho
- **Sistemas de mensajería**: Email, SMS

## Arquitectura de Conexión

### Runtime Capabilities vs Sync Connectors

Nova distingue entre dos tipos de conexiones:

#### Runtime Capabilities
Servidores MCP pensados para uso en tiempo real por agentes y chatbots:

- Responden consultas operativas
- Ejecutan acciones en vivo
- Se usan en conversaciones con usuarios
- Son la primera opción para agentes IA

**Ejemplos:**
- `taxilanz_hoteles`: Consulta disponibilidad hotelera en tiempo real
- `lageria`: Operaciones runtime de La Geria
- `sirvo`: Servicios operativos de Sirvo

#### Sync Connectors
Endpoints directos para sincronización interna:

- Importan productos, pedidos, reservas
- Refrescan catálogos
- Materializan datos externos en tablas Nova
- Se usan para operaciones de fondo
- No son para interacciones en tiempo real

**Ejemplos:**
- `lageria_woo`: Sincronización WooCommerce La Geria
- `lageria_latepoint`: Sincronización LatePoint La Geria
- `taxilanz_woo`: Sincronización WooCommerce Taxilanz

## Herramientas del Servidor Nova

### 1. list_servers
Lista todos los servidores MCP disponibles con taxonomía.

**Uso:**
```json
{
  "name": "list_servers"
}
```

**Respuesta:**
```json
{
  "servers": [
    {
      "key": "taxilanz_hoteles",
      "kind": "mcp_server",
      "role": "runtime_capability",
      "usage": "agent_runtime",
      "expose_to_agents": true,
      "preferred_for_runtime": true
    },
    {
      "key": "lageria_woo",
      "kind": "sync_connector",
      "role": "internal_sync_connector",
      "usage": "internal_sync",
      "expose_to_agents": false,
      "preferred_for_runtime": false
    }
  ]
}
```

### 2. list_capabilities
Lista solo runtime capabilities para operaciones en vivo.

**Uso:**
```json
{
  "name": "list_capabilities"
}
```

### 3. list_sync_connectors
Lista solo conectores internos de sincronización.

**Uso:**
```json
{
  "name": "list_sync_connectors"
}
```

### 4. get_server_tools
Inspecciona herramientas disponibles de un servidor específico.

**Uso:**
```json
{
  "name": "get_server_tools",
  "arguments": {
    "server": "taxilanz_hoteles"
  }
}
```

**Respuesta:**
```json
{
  "server": "taxilanz_hoteles",
  "role": "runtime_capability",
  "tools": [
    {
      "name": "check_availability",
      "description": "Consulta disponibilidad hotelera",
      "parameters": {...}
    }
  ]
}
```

### 5. call_server
Ejecuta herramientas de un servidor MCP.

**Para runtime capabilities:**
```json
{
  "name": "call_server",
  "arguments": {
    "server": "taxilanz_hoteles",
    "tool": "check_availability",
    "parameters": {
      "hotel_id": 123,
      "check_in": "2026-06-10",
      "check_out": "2026-06-15"
    }
  }
}
```

**Para sync connectors (requiere flag especial):**
```json
{
  "name": "call_server",
  "arguments": {
    "server": "lageria_woo",
    "tool": "sync_products",
    "allow_internal_connector": true
  }
}
```

### 6. list_agents
Lista agentes IA disponibles por negocio.

**Uso:**
```json
{
  "name": "list_agents",
  "arguments": {
    "nova_business_id": 1
  }
}
```

## Patrones de Integración Recomendados

### Flujo Runtime (Operaciones en vivo)
```
1. list_capabilities
2. get_server_tools(server: taxilanz_hoteles)
3. call_server(server: taxilanz_hoteles, tool: check_availability)
```

### Flujo Sync (Sincronización interna)
```
1. list_sync_connectors
2. get_server_tools(server: lageria_woo)
3. call_server(
     server: lageria_woo,
     tool: sync_products,
     allow_internal_connector: true
   )
```

## Creación de un Servidor MCP Externo

### Requisitos

Para conectar tu sistema con Nova como MCP externo:

1. **Implementar el protocolo MCP** usando el SDK oficial
2. **Definir herramientas (tools)** para las operaciones que deseas exponer
3. **Configurar el transporte** (HTTP/stdio) según tu caso de uso
4. **Proporcionar metadatos** claros sobre tu servidor

### Ejemplo de Servidor MCP (TypeScript)

```typescript
import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';

const server = new Server({
  name: "mi-sistema-mcp",
  version: "1.0.0"
}, {
  capabilities: {
    tools: {}
  }
});

// Registrar herramienta
server.setRequestHandler(ListToolsRequestSchema, async () => ({
  tools: [
    {
      name: "consultar_producto",
      description: "Consulta información de un producto",
      inputSchema: {
        type: "object",
        properties: {
          product_id: {
            type: "string",
            description: "ID del producto"
          }
        },
        required: ["product_id"]
      }
    }
  ]
}));

// Manejar llamadas a herramientas
server.setRequestHandler(CallToolRequestSchema, async (request) => {
  if (request.params.name === "consultar_producto") {
    const productId = request.params.arguments?.product_id;
    // Lógica para consultar producto
    return {
      content: [{
        type: "text",
        text: JSON.stringify({ product: {...} })
      }]
    };
  }
});

// Iniciar servidor
const transport = new StdioServerTransport();
await server.connect(transport);
```

### Registro en Nova

Una vez creado tu servidor MCP, regístralo en Nova:

1. Accede al panel Filament de Nova
2. Ve a la sección de servidores MCP
3. Crea un nuevo servidor con:
   - **Nombre**: Nombre identificativo
   - **Slug**: Identificador único
   - **Transporte**: web (HTTP) o local (CLI)
   - **Endpoint**: `/mcp/tu-slug`
   - **Instrucciones**: Descripción para agentes IA

## Chat Gateway para Canales Externos

Para integraciones conversacionales que no sean WhatsApp, usa el Chat Gateway:

**Endpoint:**
```
POST /api/nova/chat
```

**Payload:**
```json
{
  "message": "¿qué habitaciones tienen disponibles?",
  "channel": "ai_bot",
  "conversation_id": "conversation-123",
  "user": {
    "phone": "+340000001",
    "name": "Usuario",
    "locale": "es"
  },
  "context": {
    "source_url": "https://tu-sitio.com"
  }
}
```

**Respuesta:**
```json
{
  "success": true,
  "source": "nova_chat_gateway",
  "reply": "Tenemos disponibilidad en las siguientes habitaciones...",
  "conversation_id": "conversation-123",
  "nova_request_id": 169,
  "intent": "hotel_availability",
  "status": "answering_availability"
}
```

## Seguridad y Autenticación

### Middleware Disponible

Nova soporta middleware Laravel para proteger endpoints MCP:

- `throttle:mcp` - Rate limiting para MCP
- `auth:sanctum` - Autenticación Sanctum
- Middleware personalizado

### Configuración

Al registrar un servidor MCP en Nova, puedes especificar middleware:

```php
Server::create([
    'middleware' => ['throttle:mcp', 'auth:sanctum'],
    // ...
]);
```

## Casos de Uso

### Caso 1: Tienda WooCommerce

**Runtime Capability:**
- Consultar stock en tiempo real
- Verificar disponibilidad de productos
- Obtener precios actualizados

**Sync Connector:**
- Sincronizar catálogo de productos
- Importar pedidos
- Actualizar stock

### Caso 2: Sistema de Reservas

**Runtime Capability:**
- Consultar disponibilidad de citas
- Reservar slots en tiempo real
- Cancelar reservas

**Sync Connector:**
- Importar historial de reservas
- Sincronizar calendarios
- Exportar estadísticas

### Caso 3: ERP Laravel

**Runtime Capability:**
- Consultar estado de pedidos
- Verificar información de clientes
- Obtener datos de inventario

**Sync Connector:**
- Sincronizar maestros de productos
- Importar clientes
- Exportar facturas

## Soporte y Documentación

- **Documentación Nova**: `/docs/05-nova-ai-mcp-architecture.md`
- **Documentación MCP**: Protocolo estándar Model Context Protocol
- **Ejemplos**: Revisa los servidores MCP existentes en el códigobase

## Próximos Pasos

1. Revisa la arquitectura Nova MCP en `docs/05-nova-ai-mcp-architecture.md`
2. Explora los servidores MCP existentes en `app/Mcp/`
3. Crea tu servidor MCP siguiendo el protocolo estándar
4. Regístralo en Nova mediante el panel Filament
5. Prueba la integración usando las herramientas del servidor Nova
