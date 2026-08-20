# Configurar Casos de Uso Reales en Filament

> **Casos de uso**: Taxi transfer, taxi rutas, taxi + visita, taxi + restaurante, comprar aloe, comprar vinos, reservar visitas, reservar visita con taxi, reservar restaurante, ver visitas disponibles, ver restaurantes disponibles

---

## Estrategia: Configuración desde Filament

En lugar de crear condiciones hardcoded en el workflow, configuramos los casos de uso en Filament usando:
- **NovaIntentRule**: Reglas de detección de intención
- **NovaIntentToServerMapping**: Mapeo intención → MCP server + tool

El workflow `nova-master-router.json` usa `detect-nova-intent` que consulta estas tablas.

---

## Paso 1: Configurar Intents en Filament

Navegar a: Filament → Nova Intent Rules → Create

### Casos de Uso a Configurar

| Intent Key | Keywords | Descripción |
|------------|----------|-------------|
| `taxi_transfer` | taxi, transfer, aeropuerto, recoger | Taxi transfer desde/hacia aeropuerto |
| `taxi_rutas` | taxi, ruta, trayecto, recorrido | Taxi rutas personalizadas |
| `taxi_visita` | taxi, visita, bodega, llevar | Taxi + visita a bodega |
| `taxi_restaurante` | taxi, restaurante, llevar, comer | Taxi + restaurante |
| `comprar_aloe` | aloe, comprar, producto, gel | Comprar productos de aloe |
| `comprar_vinos` | vino, comprar, botella, cata | Comprar vinos |
| `reservar_visita` | visita, bodega, reservar, tour | Reservar visita a bodega |
| `reservar_visita_taxi` | visita, taxi, bodega, transporte | Reservar visita con taxi incluido |
| `reservar_restaurante` | restaurante, reservar, mesa, comer | Reservar mesa en restaurante |
| `ver_visitas` | visitas, disponibles, ver, listar | Ver visitas disponibles |
| `ver_restaurantes` | restaurantes, disponibles, ver, listar | Ver restaurantes disponibles |

### Ejemplo de Configuración

**Intent: taxi_transfer**
- Intent Key: `taxi_transfer`
- Keywords: `["taxi", "transfer", "aeropuerto", "recoger", "llevar"]`
- Priority: `1`
- Is Active: ✅

**Intent: taxi_visita**
- Intent Key: `taxi_visita`
- Keywords: `["taxi", "visita", "bodega", "llevar", "tour"]`
- Priority: `1`
- Is Active: ✅

---

## Paso 2: Configurar Mapeos en Filament

Navegar a: Filament → Nova Intent to Server Mapping → Create

### Mapeos Intent → Server + Tool

| Intent Key | Server Slug | Tool Name | Response Type |
|------------|-------------|-----------|---------------|
| `taxi_transfer` | `taxilanz-transfers-mcp` | `transfer_locations` | booking |
| `taxi_rutas` | `taxilanz-transfers-mcp` | `transfer_price_estimate` | booking |
| `taxi_visita` | `la-geria-mcp` | `lageria-latepoint-list-services` | booking |
| `taxi_restaurante` | `sirvo-restaurants-mcp` | `sirvo-restaurantes` | booking |
| `comprar_aloe` | `lanzaloe-magento` | `lanzaloe-magento-products` | order |
| `comprar_vinos` | `la-geria-mcp` | `lageria-woo-products` | order |
| `reservar_visita` | `la-geria-mcp` | `lageria-latepoint-list-bookings` | booking |
| `reservar_visita_taxi` | `la-geria-mcp` | `lageria-latepoint-run-ability` | booking |
| `reservar_restaurante` | `sirvo-restaurants-mcp` | `sirvo-dashboard-reservations` | booking |
| `ver_visitas` | `la-geria-mcp` | `lageria-latepoint-list-services` | booking |
| `ver_restaurantes` | `sirvo-restaurants-mcp` | `sirvo-restaurantes` | booking |

### Ejemplo de Configuración

**Mapping: taxi_transfer**
- Intent Key: `taxi_transfer`
- Server: `Taxilanz Transfers MCP`
- Tool Name: `transfer_locations`
- Response Type: `booking`
- Is Active: ✅

**Mapping: taxi_visita**
- Intent Key: `taxi_visita`
- Server: `La Geria Shop+Tours MCP`
- Tool Name: `lageria-latepoint-list-services`
- Response Type: `booking`
- Is Active: ✅

---

## Paso 3: Usar Workflow Maestro

El workflow `nova-master-router.json` ya está configurado para usar `detect-nova-intent`.

### Flujo del Workflow

```
Trigger (User Message)
  ↓
Action: detect-nova-intent
  - Consulta NovaIntentRule
  - Detecta intención
  - Retorna: intent, server_slug, tool_name
  ↓
Action: call-mcp-tool
  - Usa server_slug y tool_name
  - Ejecuta tool del MCP externo
  ↓
Action: normalize-mcp-response
  - Normaliza respuesta
  ↓
Action: register-nova-data
  - Registra en Nova
  ↓
End
```

---

## Ejemplos de Mensajes de Usuario

### Taxi Transfer
```
Usuario: "Necesito un taxi transfer al aeropuerto mañana a las 9"
→ Intent: taxi_transfer
→ Server: taxilanz-transfers-mcp
→ Tool: transfer_locations
```

### Taxi + Visita
```
Usuario: "Quiero un taxi para ir a una visita de bodega"
→ Intent: taxi_visita
→ Server: la-geria-mcp
→ Tool: lageria-latepoint-list-services
```

### Comprar Aloe
```
Usuario: "Quiero comprar productos de aloe"
→ Intent: comprar_aloe
→ Server: lanzaloe-magento
→ Tool: lanzaloe-magento-products
```

### Reservar Visita con Taxi
```
Usuario: "Reservar visita a bodega con taxi incluido"
→ Intent: reservar_visita_taxi
→ Server: la-geria-mcp
→ Tool: lageria-latepoint-run-ability
```

### Ver Visitas Disponibles
```
Usuario: "¿Qué visitas están disponibles?"
→ Intent: ver_visitas
→ Server: la-geria-mcp
→ Tool: lageria-latepoint-list-services
```

---

## Ventajas de esta Estrategia

1. **Configuración 100% desde Filament**: Sin código hardcoded
2. **Flexible**: Añadir nuevos casos de uso sin tocar código
3. **Escalable**: Soporta cualquier número de intents
4. **Mantenible**: Cambios en mapeos sin modificar workflows
5. **Visual**: Todos los casos de uso visibles en Filament

---

## Documentos Relacionados

- `docs/nova-workflow-examples/nova-master-router.json` — Workflow maestro
- `docs/guides/crear-workflow-mcp-filament.md` — Guía paso a paso
- `docs/guides/jerarquia-nova-mcp-servers.md` — Jerarquía visual
