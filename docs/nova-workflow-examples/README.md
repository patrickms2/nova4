# Nova Workflow Examples

> **Directorio**: `docs/nova-workflow-examples/`
> **Propósito**: Ejemplos de workflows JSON para diferentes casos de uso de Nova MCP

---

## Ejemplos Disponibles

### 1. Nova Complete Workflow ⭐
- **Archivo**: `nova-complete-workflow.json`
- **Caso de uso**: Workflow completo que integra todos los recursos de Nova (intents, prompts, cross-selling, MCP, normalización, registro)
- **Función**: Usa todos los recursos de Nova en un solo workflow visual
- **Flujo**: Detectar intención → Aplicar prompt → Ejecutar MCP → Aplicar cross-selling → Normalizar → Registrar
- **Recursos Nova integrados**: NovaIntentRule, NovaIntentToServerMapping, Prompt, NovaCrossSellingRule, NovaAiKnowledge
- **Ventaja**: Visualización completa de todos los recursos de Nova en un solo workflow

### 2. Nova Master Router
- **Archivo**: `nova-master-router.json`
- **Caso de uso**: Workflow maestro que enruta dinámicamente a MCP servers usando intents de Filament
- **Función**: Detecta intención usando `NovaIntentRule` y `NovaIntentToServerMapping` de Filament
- **Flujo**: Detectar intención (Filament) → Ejecutar MCP tool → Normalizar → Registrar en Nova
- **Ventaja**: Configuración 100% desde Filament, sin reglas hardcoded
- **Casos de uso soportados**: Taxi transfer, taxi rutas, taxi + visita, taxi + restaurante, comprar aloe, comprar vinos, reservar visitas, reservar visita con taxi, reservar restaurante, ver visitas disponibles, ver restaurantes disponibles

### 2. La Geria Agent
- **Archivo**: `la-geria-agent.json`
- **Caso de uso**: Visitas bodega y servicios La Geria
- **MCP Server**: `la-geria-mcp`
- **Tool**: `lageria-latepoint-list-services`
- **Intent**: `winery_tour`

### 3. Sirvo Restaurantes
- **Archivo**: `sirvo-restaurantes.json`
- **Caso de uso**: Reservas de restaurantes
- **MCP Server**: `sirvo-restaurants-mcp`
- **Tool**: `sirvo-restaurantes`
- **Intent**: `restaurant_booking`

### 4. Taxilanz Hoteles
- **Archivo**: `taxilanz-hoteles.json`
- **Caso de uso**: Lista de hoteles y bookings
- **MCP Server**: `taxilanz-hoteles-laravel`
- **Tool**: `hotel_list`
- **Intent**: `hotel_booking`

### 5. Lanzaloe Magento
- **Archivo**: `lanzaloe-magento.json`
- **Caso de uso**: Productos y pedidos eCommerce
- **MCP Server**: `lanzaloe-magento`
- **Tool**: `lanzaloe-magento-products`
- **Intent**: `product_purchase`

### 6. Taxilanz Transfers
- **Archivo**: `taxilanz-transfers.json`
- **Caso de uso**: Transferencias y tarifas
- **MCP Server**: `taxilanz-transfers-mcp`
- **Tool**: `transfer_locations`
- **Intent**: `transfer_booking`

---

## Estructura de Workflow

Todos los ejemplos siguen el mismo flujo simplificado:

```
Start
  ↓
Action: call-mcp-tool
  ↓
Action: normalize-mcp-response
  ↓
Action: register-nova-data
  ↓
End
```

**Nota**: Los ejemplos están simplificados para cumplir con la validación de Agentic Chatbot. Para añadir búsqueda de conocimiento o generación de respuestas con IA, añade nodos adicionales después de probar el flujo básico.

---

## Cómo Usar los Ejemplos

### Opción 1: Importar en Filament

1. Navegar a **Agentic Chatbot** → **Workflows**
2. Click en **Import** (si disponible)
3. Seleccionar el archivo JSON deseado
4. El workflow se importará con todos los nodos y conexiones
5. Publicar el workflow

### Opción 2: Crear Manualmente

1. Navegar a **Agentic Chatbot** → **Workflows** → **Create**
2. Usar el JSON como referencia para crear los nodos manualmente
3. Configurar cada nodo según el JSON
4. Conectar los nodos según los edges
5. Publicar el workflow

### Opción 3: Importar vía Tinker

```bash
php artisan tinker --execute '
use Heiner\FilamentAgenticChatbot\Models\AgentWorkflow;
use Heiner\FilamentAgenticChatbot\Models\AgentWorkflowVersion;

$json = file_get_contents("docs/nova-workflow-examples/sirvo-restaurantes.json");
$snapshot = json_decode($json, true);

$workflow = AgentWorkflow::create([
    "name" => "Sirvo Restaurantes",
    "description" => "Workflow para reservas de restaurantes",
    "is_active" => true,
]);

$version = AgentWorkflowVersion::create([
    "agent_workflow_id" => $workflow->id,
    "name" => "v1",
    "snapshot" => $snapshot,
    "is_draft" => false,
    "is_published" => true,
    "published_at" => now(),
]);

echo "Workflow importado: " . $workflow->name . "\n";
'
```

---

## Personalización

Para adaptar un ejemplo a tu caso de uso:

1. **Cambiar MCP Server**: Modifica `server_slug` en el nodo `call-mcp-tool`
2. **Cambiar Tool**: Modifica `tool_name` en el nodo `call-mcp-tool`
3. **Cambiar Input**: Modifica `input` en el nodo `call-mcp-tool` según los parámetros de la tool
4. **Cambiar Response Type**: Modifica `response_type` en el nodo `normalize-mcp-response` (booking/order/transaction)
5. **Cambiar Intent**: Modifica `intent_key` en el nodo `register-nova-data`

---

## Testing

Para probar un workflow importado:

1. Navegar a **Agentic Chatbot** → **Bots**
2. Click en **Test** del bot "Nova MCP Operator"
3. En el widget de chat, enviar un mensaje relacionado con el caso de uso
4. Verificar que el workflow se ejecuta correctamente
5. Revisar los logs en **Agentic Chatbot** → **Workflow Runs**

---

## Troubleshooting

### Workflow no se ejecuta
- Verificar que el workflow está **published**
- Verificar que el workflow está **active**
- Verificar que el bot tiene el workflow asignado

### Error en MCP tool
- Verificar que el `server_slug` es correcto
- Verificar que el `tool_name` es correcto
- Verificar que el MCP server está activo

### Error en normalización
- Verificar que el `response_type` es correcto (booking/order/transaction)
- Verificar que la respuesta MCP tiene la estructura esperada

### Error en registro
- Verificar que las tablas Nova existen
- Verificar que el `record_type` es correcto
- Verificar que el `intent_key` existe en `nova_intent_rules`

---

## Documentos Relacionados

- `docs/guides/crear-workflow-mcp-filament.md` — Guía paso a paso
- `docs/plans/nova-agentic-chatbot-integration.md` — Plan de integración
- `docs/NOVA_ARCHITECTURE_MASTER.md` — Arquitectura maestra
