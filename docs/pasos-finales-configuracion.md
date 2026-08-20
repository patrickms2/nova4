# Pasos Finales de Configuración

> **Objetivo**: Publicar workflows, configurar recursos Nova y asignar al bot

---

## Paso 1: Publicar Workflows en Filament

1. Navegar a: `https://novahubmcp.test/admin`
2. Ir a: **Agentic Chatbot** → **Workflows**
3. Para cada workflow (7 en total):
   - Click en **Editar**
   - Revisar el workflow visual
   - Click en **Publish** (botón azul)
   - Confirmar publicación

**Workflows a publicar:**
- Nova Complete Workflow (ID: 23)
- Nova Master Router (ID: 24)
- La Geria Agent (ID: 25)
- Sirvo Restaurantes (ID: 26)
- Taxilanz Hoteles (ID: 27)
- Lanzaloe Magento (ID: 28)
- Taxilanz Transfers (ID: 29)

---

## Paso 2: Usar Intents Existentes en Nova Intent Rules

**IMPORTANTE:** Ya existen 15 intents configurados en Nova. No crear nuevos, usar los existentes.

**Intents existentes relevantes:**

| Intent Key | Keywords (existentes) | Descripción |
|------------|----------------------|-------------|
| `taxi_booking` | taxi, traslado, transfer, recogida, llévame, llevame, aeropuerto | Reserva de taxi o traslado |
| `restaurant_booking` | restaurante, mesa, cenar, comer, reserva restaurante, taberna, cepa | Reserva de restaurante |
| `winery_visit` | visita bodega, bodega, la geria, cata, wine tour, visita guiada, vino | Visita o cata en bodega |
| `product_purchase` | comprar aloe, tienda aloe, quiero comprar, shop, checkout | Compra de productos |
| `product_info` | aloe, lanzaloe, vinoterapia, crema, cosmética, producto aloe | Información de productos |
| `route_recommendation` | ruta, itinerario, plan de día, recomiéndame una ruta | Recomendación de ruta |
| `commercial_info` | restaurante, bodega, taxi, producto, aloe, vino, visita, tour, hotel, ruta | Información comercial general |

**NO crear nuevos intents.** Los existentes ya cubren los casos de uso necesarios.

---

## Paso 3: Configurar Mapeos en Nova Intent to Server Mapping

1. Navegar a: **Nova Intent to Server Mapping** → **Create**
2. Crear los siguientes mapeos usando los intents existentes:

| Intent Key | Server | Tool Name | Response Type |
|------------|--------|-----------|---------------|
| `taxi_booking` | Taxilanz Transfers MCP | transfer_locations | booking |
| `taxi_booking` | Taxilanz Transfers MCP | transfer_price_estimate | booking |
| `restaurant_booking` | Sirvo Restaurants MCP | sirvo-restaurantes | booking |
| `restaurant_booking` | Sirvo Restaurants MCP | sirvo-dashboard-reservations | booking |
| `winery_visit` | La Geria Shop+Tours MCP | lageria-latepoint-list-services | booking |
| `winery_visit` | La Geria Shop+Tours MCP | lageria-latepoint-list-bookings | booking |
| `product_purchase` | Lanzaloe Magento MCP | lanzaloe-magento-products | order |
| `product_purchase` | La Geria Shop+Tours MCP | lageria-woo-products | order |
| `product_info` | Lanzaloe Magento MCP | lanzaloe-magento-products | order |
| `commercial_info` | Sirvo Restaurants MCP | sirvo-restaurantes | booking |
| `commercial_info` | La Geria Shop+Tours MCP | lageria-latepoint-list-services | booking |

3. Para cada mapeo:
   - **Intent Key**: (valor de la tabla - usar intents existentes)
   - **Server**: (seleccionar de la lista)
   - **Tool Name**: (valor de la tabla)
   - **Response Type**: booking u order
   - **Is Active**: ✅
   - Click en **Save**

---

## Paso 4: Configurar Cross-Selling en Nova Cross Selling Rules (Opcional)

1. Navegar a: **Nova Cross Selling Rules** → **Create**
2. Ejemplo de configuración:

**Ejemplo: Cross-selling de Bodega a Restaurante**
- **From Business**: La Geria
- **To Business**: Sirvo
- **Trigger Intent**: `reservar_visita`
- **Message**: "¿Te gustaría reservar una mesa en uno de nuestros restaurantes recomendados después de la visita?"
- **CTA Label**: "Ver Restaurantes"
- **CTA URL**: `https://novahubmcp.test/restaurantes`
- **Priority**: 1
- **Is Active**: ✅
- Click en **Save**

---

## Paso 5: Asignar Workflows al Bot Nova MCP Operator

1. Navegar a: **Agentic Chatbot** → **Agents**
2. Buscar el bot: **Nova MCP Operator**
3. Click en **Editar**
4. En la sección **Workflows**:
   - Seleccionar: **Nova Complete Workflow** (recomendado)
   - O seleccionar: **Nova Master Router** (alternativa)
5. Click en **Save**

---

## Paso 6: Probar el Flujo Completo

1. Navegar a: **Agentic Chatbot** → **Chat**
2. Seleccionar el bot: **Nova MCP Operator**
3. Probar con mensajes de ejemplo:

**Test 1: Taxi Transfer**
```
Usuario: "Necesito un taxi transfer al aeropuerto mañana a las 9"
```

**Test 2: Taxi + Visita**
```
Usuario: "Quiero un taxi para ir a una visita de bodega"
```

**Test 3: Comprar Aloe**
```
Usuario: "Quiero comprar productos de aloe"
```

**Test 4: Ver Visitas**
```
Usuario: "¿Qué visitas están disponibles?"
```

---

## Verificación

Después de completar todos los pasos, verifica:

- ✅ 7 workflows publicados en Filament
- ✅ 11 intents configurados en Nova Intent Rules
- ✅ 11 mapeos configurados en Nova Intent to Server Mapping
- ✅ Nova Complete Workflow asignado al bot Nova MCP Operator
- ✅ Pruebas de chat funcionando correctamente

---

## Documentos Relacionados

- `docs/guides/configurar-casos-uso-reales-filament.md` — Configuración detallada de intents
- `docs/guides/unificar-recursos-nova-agentic-chatbot.md` — Recursos Nova unificados
- `docs/nova-workflow-examples/README.md` — Ejemplos de workflows
