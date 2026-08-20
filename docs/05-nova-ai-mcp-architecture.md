# Nova AI + MCP Architecture

## 0. Taxonomía prioritaria: runtime capabilities vs sync connectors

Nova separa dos conceptos que no deben mezclarse en la capa de agentes:

### Runtime capabilities

Son servidores MCP o capacidades operativas pensadas para uso en tiempo real por agentes, chatbots, herramientas IDE o flujos conversacionales.

Uso:

- responder consultas operativas,
- consultar herramientas disponibles,
- ejecutar acciones en vivo,
- enrutar una petición del usuario hacia el sistema correcto,
- dar contexto al orquestador.

Ejemplos:

- `nova`
- `sirvo`
- `taxilanz_hoteles`
- `taxilanz_woo` cuando representa el MCP operativo de rutas/Chauffeur
- `lageria` si representa un MCP operativo de La Geria
- `lanzaloe` si representa un MCP operativo o wrapper runtime

Contrato recomendado:

```json
{
  "key": "taxilanz_hoteles",
  "kind": "mcp_server",
  "role": "runtime_capability",
  "usage": "agent_runtime",
  "expose_to_agents": true,
  "preferred_for_runtime": true,
  "fallback_only": false
}
```

### Sync connectors

Son endpoints directos/API usados por Nova para sincronización interna, enriquecimiento de datos o fallback técnico cuando una capacidad MCP no cubre una operación.

Uso:

- importar productos,
- importar reservas,
- refrescar catálogos,
- materializar datos externos en tablas Nova,
- complementar capacidades que no da un MCP operativo,
- fallback controlado.

No son la primera opción para agentes conversacionales.

Ejemplos:

- `lageria_woo`
- `lageria_latepoint`
- `taxilanz_woo` cuando representa WooCommerce directo
- `lanzaloe_magento`

Contrato recomendado:

```json
{
  "key": "lageria_latepoint",
  "kind": "sync_connector",
  "role": "internal_sync_connector",
  "usage": "internal_sync",
  "expose_to_agents": false,
  "preferred_for_runtime": false,
  "fallback_only": true
}
```

### Regla de prioridad

```text
Agentes y chatbots deben preferir runtime capabilities.
Sync connectors solo se usan para sincronización interna, enriquecimiento o fallback explícito.
```

En el MCP `nova`, las llamadas a sync connectors mediante `call_server` requieren `allow_internal_connector=true`.

### Tools del MCP Nova

Endpoint:

```text
https://novahubmcp.test/mcp/nova
```

Tools:

- `list_agents`: lista perfiles IA disponibles.
- `list_capabilities`: lista solo runtime capabilities/MCPs operativos.
- `list_sync_connectors`: lista solo conectores internos de sincronización.
- `list_servers`: compatibilidad; lista ambos tipos con campos de taxonomía.
- `get_server_tools`: devuelve herramientas de una capability o connector, indicando `role` y `usage`.
- `call_server`: ejecuta runtime capabilities por defecto; para connectors internos exige `allow_internal_connector=true`.

Flujo recomendado:

```text
list_capabilities
→ get_server_tools(server: taxilanz_hoteles)
→ call_server(server: taxilanz_hoteles, tool: ...)
```

Flujo interno/fallback:

```text
list_sync_connectors
→ get_server_tools(server: lageria_latepoint)
→ call_server(
    server: lageria_latepoint,
    tool: list_services,
    allow_internal_connector: true
  )
```

### Chat Gateway para canales externos

Los canales conversacionales externos no deben llamar directamente a MCPs ni sync connectors. Deben comunicarse con Nova mediante el gateway conversacional:

```text
POST /api/nova/chat
```

Uso:

- `/ai-bot`
- widgets web externos
- integraciones conversacionales futuras
- pruebas de canal que no sean WhatsApp Cloud

Payload recomendado:

```json
{
  "message": "qué puedo hacer?",
  "channel": "ai_bot",
  "conversation_id": "ai-bot-demo",
  "user": {
    "phone": "+340000001",
    "name": "Patrick",
    "locale": "es"
  },
  "context": {
    "source_url": "https://novahubmcp.test/ai-bot"
  }
}
```

Respuesta recomendada:

```json
{
  "success": true,
  "source": "nova_chat_gateway",
  "reply": "...",
  "conversation_id": "ai-bot-demo",
  "nova_request_id": 169,
  "intent": "commercial_info",
  "status": "answering_commercial_info"
}
```

El gateway llama a `NovaOrchestratorService`. El orquestador decide si debe recuperar knowledge, usar runtime capabilities MCP o, excepcionalmente, usar sync connectors como fallback interno.

Las selecciones cortas como `1`, `2`, `3`, `4` deben interpretarse según el menú conversacional anterior. Por ejemplo, después de una respuesta de planes en Lanzarote donde la opción `4` es gastronomía/restaurante, `4` debe continuar como `restaurant_booking`, no como taxi.

### Reservas de rutas taxi con CHBS + WooCommerce

Para Taxilanz, la reserva definitiva de una ruta taxi no debe crearse como un pedido WooCommerce genérico ni como una reserva operativa definitiva antes del pago.

El flujo correcto es:

```text
Nova chat / widget
→ recoge origen, destino, fecha, hora, pasajeros y contacto
→ crea una intención/pre-reserva en Nova
→ muestra o embebe el formulario de ruta taxi equivalente a CHBS
→ CHBS calcula ruta, distancia, duración, vehículo y precio
→ WooCommerce/Redsys procesa el pago
→ al pago correcto, CHBS crea la reserva definitiva
→ Nova sincroniza la reserva pagada hacia TaxiBooking / panel operativo
```

Fuente de verdad por fase:

- Antes del pago: Nova conserva una intención o pre-reserva trazable.
- Durante el checkout: CHBS + WooCommerce gestionan precio, carrito, pedido y Redsys.
- Después del pago: CHBS/WooCommerce son la fuente de verdad comercial; Nova proyecta la reserva a `TaxiBooking` para operación y seguimiento.

Regla importante:

```text
No crear Woo orders genéricos para rutas taxi.
No confirmar una reserva operativa final si el flujo requiere pago online y todavía no hay pago aprobado.
```

Si el canal es conversacional, Nova puede devolver un enlace de pago o abrir un formulario embebido con los datos pre-rellenados. La confirmación final debe llegar por webhook/sync desde WooCommerce/CHBS.

## 1. Descripción general

Nova es la capa de orquestación conversacional y operativa para conectar negocios turísticos, comercios, taxis, restaurantes y servicios locales de Lanzarote.

El objetivo de esta fase es que Nova pueda atender al cliente final desde WhatsApp o widget web, entender el contexto de la conversación y derivar hacia la acción correcta:

- informar,
- reservar,
- vender,
- recomendar,
- pedir datos,
- consultar disponibilidad,
- preparar una solicitud,
- conectar con un MCP externo,
- registrar trazabilidad comercial.

Nova no sustituye necesariamente a las plataformas existentes. Actúa como capa inteligente sobre ellas.

## 2. Principios de arquitectura

- **NovaBusiness** representa una empresa cliente.
- **NovaService** representa un servicio contratado o activado para esa empresa.
- **NovaMcpServer** conecta Nova con sistemas externos.
- **NovaWhatsappChannel** conecta WhatsApp Cloud API con la empresa/servicio.
- **NovaAiProfile** define la personalidad/configuración del asistente.
- **NovaAiKnowledge** almacena conocimiento comercial, operativo y contextual.
- **NovaRequest** registra conversaciones, solicitudes y estado operativo.

La arquitectura actual sigue el patrón:

```text
Empresa → Servicios → Capacidades → Canales / MCP / IA / Knowledge
```

## 3. Modelos principales

### NovaBusiness

Modelo: `App\Models\NovaBusiness`

Representa negocios como:

- La Geria,
- Lanzaloe,
- Taxilanz,
- El Cangrejo Rojo,
- Sirvo,
- Nova.

Campos clave:

- `name`
- `slug`
- `business_type`
- `status`
- `contact_name`
- `contact_email`
- `contact_phone`
- `website_url`
- `subscription_amount`
- `commission_rate`
- `settings`

Relaciones:

- `services()`
- `mcpServers()`
- `whatsappChannels()`
- `aiProfiles()`
- `aiKnowledge()`

### NovaService

Modelo: `App\Models\NovaService`

Representa el servicio específico contratado por una empresa.

Campos clave:

- `nova_business_id`
- `name`
- `code`
- `service_type`
- `status`
- `has_development`
- `has_maintenance`
- `has_whatsapp`
- `has_mcp`
- `has_sales`
- `has_services`
- `monthly_amount`
- `commission_rate`
- `settings`
- `notes`

Uso previsto:

- activar WhatsApp,
- activar MCP,
- activar ventas,
- activar servicios/reservas,
- definir comisiones,
- agrupar canales e inteligencia por servicio.

### NovaMcpServer

Representa una integración externa conectable por MCP o API.

Tipos actuales/conceptuales:

- Sirvo / restaurante,
- La Geria / WordPress MCP,
- Taxilanz / WooCommerce MCP,
- Lanzaloe / Magento MCP,
- Lanzaloe visitas / Laravel,
- plataforma hoteles / Laravel,
- otros sistemas externos futuros.

### NovaWhatsappChannel

Modelo: `App\Models\NovaWhatsappChannel`

Representa el canal WhatsApp Cloud API asociado a una empresa y servicio.

Campos clave:

- `nova_business_id`
- `nova_service_id`
- `name`
- `provider`
- `phone_number`
- `phone_number_id`
- `business_account_id`
- `webhook_url`
- `status`
- `credentials`
- `settings`

### NovaAiProfile

Representa el perfil de IA de una empresa/servicio.

Uso previsto:

- tono,
- idioma,
- instrucciones comerciales,
- comportamiento del asistente,
- reglas de escalado,
- prompt base.

### NovaAiKnowledge

Modelo: `App\Models\NovaAiKnowledge`

Almacena fragments de conocimiento.

Campos clave:

- `nova_business_id`
- `nova_service_id`
- `nova_ai_profile_id`
- `title`
- `content`
- `status`
- `metadata`
- `embedding`
- `vectorized_at`

Uso actual:

- información de visitas,
- cartas de restaurantes,
- tarifas de taxi,
- rutas turísticas,
- productos,
- instrucciones operativas,
- CTAs comerciales,
- enlaces relevantes.

### NovaRequest

Registra conversaciones y solicitudes generadas por el orquestador.

Uso actual:

- guardar mensaje original,
- guardar estado conversacional,
- guardar knowledge recuperado,
- guardar checks de MCP,
- guardar respuesta generada,
- mantener continuidad por teléfono.

## 4. Servicios principales

### NovaOrchestratorService

Archivo: `app/Services/Nova/NovaOrchestratorService.php`

Responsabilidad:

- recibir mensaje,
- detectar negocio,
- detectar intención,
- recuperar conversación previa,
- consultar MCPs cuando aplique,
- recuperar knowledge,
- construir respuesta final,
- registrar `NovaRequest`.

Intenciones actuales:

- `commercial_info`
- `restaurant_booking`
- `winery_visit`
- `restaurant_and_winery_visit`
- `taxi_booking`
- `unknown`

Capacidades actuales:

- menú numerado cuando la intención es ambigua,
- respuestas comerciales con CTA,
- selección de negocio por keywords,
- priorización de fragments relevantes,
- respuestas compactas para WhatsApp,
- tolerancia a MCP caído.

### NovaConversationDataExtractor

Archivo: `app/Services/Nova/NovaConversationDataExtractor.php`

Responsabilidad:

- detectar intención,
- extraer fecha,
- extraer hora,
- extraer número de personas,
- conservar contexto anterior,
- mapear respuestas numéricas del menú,
- resolver stage conversacional.

Ejemplos de detección:

```text
"qué puedo hacer en Lanzarote" → commercial_info
"quiero comer en La Cepa" → restaurant_booking
"necesito traslado al aeropuerto" → taxi_booking
"excursión en taxi por la isla" → taxi_booking
"info visitas La Geria" → commercial_info
"reservar mesa mañana a las 14 para 2" → restaurant_booking
```

### NovaKnowledgeService

Archivo: `app/Services/Nova/NovaKnowledgeService.php`

Responsabilidad:

- recibir negocio y mensaje,
- buscar knowledge activo,
- puntuar por términos,
- devolver fragments relevantes.

Estado actual:

- scoring simple por coincidencia textual,
- límite configurable,
- preparado para evolucionar a embeddings/vectorización.

### NovaWhatsAppCloudService

Archivo: `app/Services/Nova/NovaWhatsAppCloudService.php`

Responsabilidad:

- enviar mensajes por Meta WhatsApp Cloud API,
- marcar mensajes como leídos,
- enviar reacciones,
- normalizar teléfonos.

Configuración:

- `services.nova.whatsapp_phone_number_id`
- `services.nova.whatsapp_access_token`
- `services.nova.meta_graph_version`

### NovaWebsiteKnowledgeImporter

Archivo: `app/Services/Nova/NovaWebsiteKnowledgeImporter.php`

Responsabilidad:

- importar contenido básico desde `NovaBusiness.website_url`,
- limpiar HTML,
- crear fragmento en `nova_ai_knowledge`,
- asociarlo al negocio, servicio y perfil de IA cuando exista.

## 4.6 NovaAiService

Archivo: `app/Services/Nova/NovaAiService.php`

Responsabilidad:

- detectar intención del mensaje vía OpenAI (`gpt-4o-mini`).
- extraer datos de reserva estructurados desde el mensaje.
- generar respuesta conversacional natural.

Todos sus system prompts se cargan ahora desde el modelo `Prompt` de MCP vía `NovaPromptLoader`, con fallback al texto hardcoded si no están instalados.

| Método | Prompt DB | Fallback |
|---|---|---|
| `detectIntent()` | `nova-intent-detection` | hardcoded |
| `extractBookingData()` | `nova-booking-extraction` | hardcoded |
| `generateResponse()` | `nova-response-generation` | hardcoded |

## 4.7 NovaIntentExtractorService

Archivo: `app/Services/Nova/NovaIntentExtractorService.php`

Responsabilidad:

- llamar al modelo Ollama local (`qwen3:4b`) para extraer intención, fecha, hora, personas, origen y destino.
- normalizar y heredar datos de la conversación previa.
- resolver el `stage` conversacional.

El system prompt Ollama se carga desde `NovaPromptLoader::system('nova-ollama-intent')` con fallback hardcoded.

URL del modelo Ollama configurable en `services.ollama.url` (por defecto `http://ai.novagestion.eu:11434`).

## 4.8 NovaCrossSellingService

Archivo: `app/Services/Nova/NovaCrossSellingService.php`

Responsabilidad:

- devolver sugerencias de cross-selling entre negocios según el negocio actual y la intención detectada.

Matriz de reglas (ahora también editable en Filament como prompt `nova-cross-selling-rules`):

```text
la-geria + restaurant_booking → Taxilanz [high], Lanzaloe [medium]
la-geria + winery_visit       → Lanzaloe [medium], Sirvo [high]
lanzaloe + winery_visit       → La Geria [high], Taxilanz [medium]
sirvo    + restaurant_booking → La Geria [high], Taxilanz [medium]
taxilanz + taxi_booking       → La Geria [high], Sirvo [medium], Lanzaloe [medium]
```

## 4.9 NovaConversationContextService

Archivo: `app/Services/Nova/NovaConversationContextService.php`

Responsabilidad:

- cargar historial de `NovaRequest` por teléfono (caché 24h).
- detectar negocios visitados, patrones de horario, tamaño de grupo habitual.
- generar sugerencias contextuales ("la última vez reservaste para 2…").
- sugerir cross-selling basado en historial de visitas.

---

## 4.10 NovaServicesPromptCatalog ✨ nuevo

Archivo: `app/Services/NovaServicesPromptCatalog.php`

Responsabilidad:

- definir el catálogo canónico de los 6 prompts editables de `Services/Nova`.
- crear automáticamente el servidor MCP `nova-services` si no existe.
- instalar prompts en DB sin sobreescribir ediciones (`install()`).
- reinstalar resetando al contenido por defecto (`reinstall()`).

Prompts incluidos:

| Nombre DB | Servicio que lo usa | Método |
|---|---|---|
| `nova-intent-detection` | `NovaAiService` | `detectIntent()` |
| `nova-booking-extraction` | `NovaAiService` | `extractBookingData()` |
| `nova-response-generation` | `NovaAiService` | `generateResponse()` |
| `nova-ollama-intent` | `NovaIntentExtractorService` | `extract()` |
| `nova-cross-selling-rules` | `NovaCrossSellingService` | referencia documental |
| `nova-orchestrator` | `NovaOrchestratorService` | instrucciones de alto nivel |

Acciones desde Filament → `/admin/prompts`:

- **Install Nova Prompts**: instala los que no existen (seguro, preserva ediciones).
- **Reinstall Nova Prompts**: elimina y recrea todos (reset completo).

## 4.11 NovaPromptLoader ✨ nuevo

Archivo: `app/Services/NovaPromptLoader.php`

Responsabilidad:

- leer el contenido del primer mensaje `system` de un `Prompt` activo por nombre.
- cachear en memoria para no hacer N queries por request.
- devolver el `$fallback` hardcoded si el prompt no está instalado.
- limpiar caché con `NovaPromptLoader::clearCache()` (lo hacen las acciones de Filament tras instalar).

Uso en servicios:

```php
// Con fallback automático al texto hardcoded
NovaPromptLoader::system('nova-intent-detection', $this->defaultPrompt());

// Array completo de mensajes del prompt
NovaPromptLoader::messages('nova-response-generation');
```

---

## 5. Tipos de MCP e integraciones

## 5.1 Taxilanz

### Plataforma correcta

```text
Taxilanz rutas / excursiones / tarifas = WooCommerce vía MCP
```

No debe confundirse con Magento.

### Uso previsto

- rutas en taxi,
- excursiones,
- productos WooCommerce,
- pedidos,
- tarifas,
- posibles cupones internos,
- trazabilidad de origen.

### Knowledge actual preparado

Comando:

```bash
php artisan nova:seed-taxi-knowledge
```

Archivo:

```text
app/Console/Commands/NovaSeedTaxiKnowledge.php
```

Incluye:

- servicios principales,
- traslados,
- excursiones en taxi,
- contacto Taxilanz,
- tarifas desde Playa Blanca,
- tarifas desde Puerto del Carmen,
- tarifas desde Matagorda,
- tarifas desde Costa Teguise,
- tarifas desde Haría,
- tarifas desde Tinajo,
- tarifas desde La Santa Sport,
- rutas norte/sur,
- senderismo,
- recomendaciones de rutas.

Pendiente:

- crear `NovaBusiness` Taxilanz,
- asociar servicio,
- asociar MCP WooCommerce,
- ejecutar seed.

## 5.2 Plataforma de hoteles

### Plataforma correcta

```text
Hoteles / códigos / visitas / atribución = Laravel externo
```

Uso previsto:

- códigos de referencia,
- suscripciones,
- descuentos,
- atribución en tienda física,
- reservas o visitas relacionadas con hoteles,
- reporting.

Esta plataforma es distinta de WooCommerce y distinta de Magento.

## 5.3 Lanzaloe

### Plataformas correctas

```text
Lanzaloe venta online = Magento
Lanzaloe visitas / códigos / atribución física = Laravel
```

Acuerdo comercial:

- `20%` de comisión sobre compras online,
- `10%` sobre compras físicas de clientes que vayan con visita a finca reservada,
- sin setup inicial en la propuesta comercial.

Uso previsto:

- Magento para catálogo, productos, carrito, pedidos, cupones internos y ventas online,
- Laravel para visitas a finca, códigos, reservas y atribución en tienda física,
- Nova para WhatsApp, widget, recomendación y trazabilidad.

Knowledge actual:

Comando:

```bash
php artisan nova:seed-lanzaloe-knowledge
```

Archivo:

```text
app/Console/Commands/NovaSeedLanzaloeKnowledge.php
```

Incluye:

- productos,
- aloe vera,
- vinoterapia,
- categorías comerciales,
- recomendaciones.

## 5.4 La Geria

### Plataforma actual

La Geria se integra con knowledge propio y MCP/WordPress cuando aplique.

Knowledge actual:

Comando:

```bash
php artisan nova:seed-la-geria-knowledge
```

Archivo:

```text
app/Console/Commands/NovaSeedLaGeriaKnowledge.php
```

Incluye:

- visitas guiadas,
- wine tours,
- avisos de accesibilidad,
- idiomas,
- vinos,
- precios,
- historia,
- eventos,
- Taberna La Cepa.

Puntos importantes:

- Taberna La Cepa es el restaurante de Bodega La Geria.
- Visitas guiadas incluyen finca, viñedos, bodega y cata de tres vinos.
- Duración aproximada: 50 minutos.
- Precio conocido: 15€.
- Menores de 15 años gratis.
- Grupos de más de 8 personas deben contactar con la bodega por email.

## 5.5 El Cangrejo Rojo

Knowledge preparado:

Comando:

```bash
php artisan nova:seed-cangrejo-rojo-knowledge
```

Archivo:

```text
app/Console/Commands/NovaSeedCangrejoRojoKnowledge.php
```

Incluye:

- descripción,
- historia,
- contacto,
- carta,
- platos destacados,
- reservas,
- cancelaciones,
- redes sociales.

Regla importante:

```text
Nunca dar la carta completa.
```

El asistente debe:

- describir la carta,
- mencionar platos destacados,
- redirigir a la carta completa:

```text
https://www.restaurantecangrejorojo.com/la-carta/
```

Pendiente:

- crear `NovaBusiness` El Cangrejo Rojo,
- ejecutar seed,
- asociar servicio WhatsApp/MCP si aplica.

## 5.6 Sirvo / Restaurante genérico

Knowledge preparado:

Comando:

```bash
php artisan nova:seed-sirvo-restaurant-knowledge
```

Archivo:

```text
app/Console/Commands/NovaSeedSirvoRestaurantKnowledge.php
```

Uso actual:

- fallback para restaurante,
- patrón de reservas,
- disponibilidad,
- alergias,
- preferencias,
- nombre del cliente.

## 6. WhatsApp y widget

## 6.1 WhatsApp como interfaz principal

WhatsApp se considera la interfaz principal porque:

- el cliente ya lo usa,
- no requiere instalar app,
- permite conversación natural,
- facilita cierres rápidos,
- permite seguimiento,
- reduce llamadas repetitivas,
- permite CTA directo.

Ejemplo de menú:

```text
Perfecto 😊 ¿Qué te gustaría hacer?
1. Comer o reservar restaurante
2. Visitar una bodega / wine tour
3. Pedir taxi, traslado o excursión en taxi
4. Recibir información o recomendaciones
```

## 6.2 Widget web

Uso previsto:

- widget gratuito informativo,
- entrada web hacia Nova,
- generación de leads,
- conexión con WhatsApp,
- atribución de ventas/reservas.

En Lanzaloe se planteó como parte de la propuesta comercial:

```text
Widget gratuito + sin setup a cambio de comisión mayor.
```

## 7. Knowledge seeds actuales

Comandos disponibles/preparados:

```bash
php artisan nova:seed-la-geria-knowledge
php artisan nova:seed-lanzaloe-knowledge
php artisan nova:seed-sirvo-restaurant-knowledge
php artisan nova:seed-cangrejo-rojo-knowledge
php artisan nova:seed-taxi-knowledge
```

Estado:

- La Geria: preparado y ejecutado.
- Lanzaloe: preparado.
- Sirvo restaurante: preparado y ejecutado.
- Cangrejo Rojo: preparado, falta negocio.
- Taxilanz taxi: preparado, falta negocio.

## 8. Flujo conversacional actual

Entrada:

```text
WhatsApp / widget / demo artisan / ServerChat Filament
```

Proceso:

```text
Mensaje usuario
→ probar MCPs externos activos (Sirvo, La Geria)
→ recuperar conversación previa por teléfono (NovaConversationContextService)
→ NovaConversationDataExtractor:
     → NovaIntentExtractorService (Ollama, prompt nova-ollama-intent editable)
     → NovaAiService::detectIntent()  (OpenAI, prompt nova-intent-detection editable)
     → normalizar intención + stage + datos extraídos
→ resolver NovaBusiness por keywords del mensaje
→ NovaKnowledgeService: recuperar fragments relevantes
→ NovaCrossSellingService: reglas de cross-selling (editables en Filament)
→ comprobar disponibilidad si intent = restaurant_booking
→ NovaAiService::generateResponse() (OpenAI, prompt nova-response-generation editable)
     o respuestas basadas en stage/reglas PHP como fallback
→ guardar NovaRequest
```

Ejemplos:

```text
"cuánto cuesta un taxi de Puerto del Carmen a La Geria"
→ Taxilanz
→ commercial_info o taxi_booking según texto
→ knowledge tarifas taxi
```

```text
"quiero reservar mesa mañana a las 20 para 4"
→ restaurante
→ restaurant_booking
→ pedir nombre/preferencias o comprobar disponibilidad
```

```text
"qué puedo comer en Taberna La Cepa"
→ La Geria
→ commercial_info
→ knowledge Taberna La Cepa
```

```text
"quiero comprar aloe para después del sol"
→ Lanzaloe
→ commercial_info / sales intent futuro
→ Magento futuro
```

## 8.2 Chat MCP por servidor ✨ nuevo

Cada servidor MCP en Filament tiene ahora una vista de chat interactiva:

Ruta: `/admin/servers/{server}/chat`

Componentes:

### ServerChat (Livewire)

Archivo: `app/Livewire/ServerChat.php`

Capacidades:

- carga el servidor MCP y sus tools/prompts activos.
- permite seleccionar el prompt del servidor como contexto del agente.
- selección automática de tool mediante scoring heurístico sobre:
  - palabras clave del prompt activo,
  - `title` + `description` de cada tool,
  - palabras del mensaje del usuario.
- permite override manual del modo: **auto** / **forzar tool concreta**.
- ejecuta la tool seleccionada vía `ToolExecutor`.
- genera un **workflow plan** estructurado con la decisión, rationale y stages.
- muestra pasos de ejecución detallados en la UI.

### WorkflowPlanDisplay (Livewire)

Archivo: `app/Livewire/WorkflowPlanDisplay.php`

- visualiza el plan de decisión con strategy, stages y nodos.
- muestra tipo de estrategia, duración estimada, hora de generación.
- expandible/colapsable.
- portado y adaptado desde PromptlyAgent.

### Integración con prompts MCP

El chat lee el prompt activo del servidor:

```php
// Dentro de ServerChat
$prompt = $server->prompts
    ->where('is_active', true)
    ->first();

// Se expone en UI como contexto del agente
// Se usa para scoring heurístico de selección de tool
```

Los prompts del servidor se editan desde:

- `/admin/servers/{server}` → pestaña Prompts
- `/admin/prompts` → lista global

---

## 9. Filament UI Nova Businesses

La gestión de negocios Nova usa páginas laterales tipo `ManageRelatedRecords`, no relation-manager tabs.

Rutas actuales en `NovaBusinessResource`:

- `/servicios`
- `/whatsapp`
- `/mcp`
- `/ia`
- `/conocimiento-ia`

Subnavegación:

- Servicios siempre visible.
- WhatsApp visible si hay servicio con `has_whatsapp`.
- MCP visible si hay servicio con `has_mcp`.
- IA visible si hay servicio con `has_whatsapp`.
- **Conocimiento IA siempre visible** para todos los negocios (sin condición de perfil IA).

Recurso global:

- `/admin/nova-ai-knowledge` — lista todos los fragmentos de todos los negocios con filtros por negocio y estado. Permite crear, editar, borrar y hacer búsqueda por título. Disponible en el menú Nova.

## 10. Estado actual por negocio

### La Geria

Estado:

- negocio existente,
- knowledge sembrado,
- visitas/wine tours definidos,
- Taberna La Cepa definida como restaurante de la bodega,
- detección contextual corregida para `taberna` y `cepa`.

### Lanzaloe

Estado:

- knowledge preparado y ejecutado (12 fragmentos en DB),
- propuesta comercial cerrada conceptualmente,
- integración pendiente con Magento y Laravel visitas.

Acuerdo:

- 20% online,
- 10% tienda física con visita reservada.

### Taxilanz

Estado:

- seed knowledge preparado y ejecutado (17 fragmentos en DB),
- tarifas y rutas añadidas,
- falta crear negocio `taxilanz`,
- falta MCP WooCommerce de rutas/pedidos.

### El Cangrejo Rojo

Estado:

- seed knowledge preparado y ejecutado (7 fragmentos en DB),
- falta crear negocio `cangrejo-rojo`,
- reglas de carta y cancelación añadidas.

### Sirvo

Estado:

- negocio existente,
- seed genérico de restaurante ejecutado,
- usado como fallback de restaurante.

## 11. TODO técnico

### Alta de negocios

- [ x] Crear `NovaBusiness` Taxilanz.
- [x ] Crear `NovaBusiness` Lanzaloe si no existe.
- [x ] Crear `NovaBusiness` El Cangrejo Rojo.
- [x ] Revisar datos de contacto, web, tipo y estado de cada negocio.

### Servicios Nova

- [x ] Crear `NovaService` WhatsAppBot para Taxilanz.
- [x ] Crear `NovaService` WhatsAppBot para Lanzaloe.
- [x ] Crear `NovaService` WhatsAppBot para El Cangrejo Rojo.
- [x ] Definir `commission_rate` por servicio.
- [x ] Definir `has_whatsapp`, `has_mcp`, `has_sales`, `has_services` según negocio.

### Knowledge

- [x] Seeds ejecutados: La Geria (17), Taxilanz (17), Lanzaloe (12), Cangrejo Rojo (7), Sirvo (3). Total 56 fragmentos en DB.
- [x] `NovaAiKnowledgeResource` global en `/admin/nova-ai-knowledge` para editar desde Filament.
- [x] `ManageNovaBusinessAiKnowledge` visible para todos los negocios sin restricción.
- [ x] Añadir más productos reales de Lanzaloe si se obtiene catálogo completo.
- [x ] Añadir horarios reales de Cangrejo Rojo si se confirman.
- [x ] Añadir PDFs/enlaces reales de senderismo Taxilanz.
- [x ] Añadir rutas WooCommerce reales de Taxilanz.

### MCPs

- [x ] Diseñar MCP WooCommerce para Taxilanz.
- [x ] Diseñar MCP Magento para Lanzaloe ventas online.
- [x ] Diseñar integración Laravel para Lanzaloe visitas/códigos.
- [x ] Diseñar integración Laravel para plataforma hoteles/códigos.
- [x ] Normalizar tipos de `NovaMcpServer`.
- [x ] Añadir health checks robustos por tipo de MCP.
- [x ] Definir timeout/retry por integración.

### Conversación e IA

- [x] System prompts editables desde Filament (`nova-intent-detection`, `nova-booking-extraction`, `nova-response-generation`, `nova-ollama-intent`).
- [x] Cross-selling rules documentadas en prompt editable (`nova-cross-selling-rules`).
- [x] Instrucciones del orquestador en prompt editable (`nova-orchestrator`).
- [x] `NovaPromptLoader` con caché en memoria y fallback automático.
- [x] Acciones Install / Reinstall Nova Prompts en `/admin/prompts`.
- [ x] Añadir intent explícito `sales_purchase`.
- [x ] Añadir intent explícito `route_recommendation`.
- [x ] Añadir intent explícito `cancellation_request`.
- [ x] Añadir intent explícito `physical_store_visit`.
- [ x] Mejorar detección de idiomas.
- [ x] Mejorar selección de negocio cuando el mensaje no menciona marca.
- [x ] Persistir estado conversacional más estructurado.
- [ x] Evitar que preguntas informativas pidan fecha/hora/personas demasiado pronto.
- [x ] Añadir respuestas con enlaces cuando el canal permita preview.
### Atribución y comisiones

- [x] Diseñar tabla de atribución comercial.
- [x] Guardar `source_channel`.
- [x] Guardar `coupon_code`.
- [x ] Guardar `external_order_id`.
- [x ] Guardar `external_visit_id`.
- [x ] Guardar importe y comisión.
- [x ] Generar informe mensual por negocio.
- [x ] Conciliar informe Nova con ventas externas.

Modelo conceptual:

```text
lead_id
nova_business_id
nova_service_id
channel
source
coupon_code
external_order_id
external_visit_id
amount
commission_rate
commission_amount
status
metadata
```

### WhatsApp / widget

- [ ] Terminar alta real de WhatsApp por negocio.
- [ ] Crear widget web embebible.
- [ ] Conectar widget con Nova.
- [ ] Añadir tracking de origen widget/WhatsApp.
- [ ] Añadir handoff humano.
- [ ] Añadir respuestas rápidas/botones si se usan templates o interactive messages.

### Filament / Operación

- [x] Chat MCP por servidor con selección automática de tool y workflow plan (`ServerChat`).
- [x] WorkflowPlanDisplay integrado en chat MCP.
- [x] Prompts editables por servidor desde `/admin/prompts` y relation manager.
- [x] Acciones Install / Reinstall Nova Prompts en `/admin/prompts`.
- [x] `NovaAiKnowledgeResource` global para editar/añadir knowledge desde `/admin/nova-ai-knowledge`.
- [x] Conocimiento IA visible siempre en subnav de cada negocio.
- [ ] Revisar pantallas laterales de Nova Businesses.
- [ ] Añadir panel de estado MCP por negocio.
- [ ] Añadir panel de conversaciones recientes.
- [ ] Añadir panel de ventas/leads atribuidos.
- [ ] Añadir filtros por negocio/servicio/status.

### Testing

- [ ] Crear tests para `NovaConversationDataExtractor`.
- [ ] Crear tests para selección de negocio.
- [ ] Crear tests para knowledge ranking.
- [ ] Crear tests para respuestas comerciales.
- [ ] Crear tests para MCP caído.
- [ ] Crear tests para cancelación Cangrejo Rojo.
- [ ] Crear tests para rutas Taxilanz.

## 12. Comandos útiles

Validar sintaxis:

```bash
php -l app/Services/Nova/NovaConversationDataExtractor.php
php -l app/Services/Nova/NovaOrchestratorService.php
```

Ejecutar seeds:

```bash
php artisan nova:seed-la-geria-knowledge
php artisan nova:seed-lanzaloe-knowledge
php artisan nova:seed-sirvo-restaurant-knowledge
php artisan nova:seed-cangrejo-rojo-knowledge
php artisan nova:seed-taxi-knowledge
```

Probar conversación:

```bash
php artisan nova:orchestrate-demo 'qué puedo hacer en lanzarote' --phone=+340000001
php artisan nova:orchestrate-demo 'quiero comer en taberna la cepa' --phone=+340000002
php artisan nova:orchestrate-demo 'cuanto cuesta un taxi de puerto del carmen a la geria' --phone=+340000003
php artisan nova:orchestrate-demo 'qué platos destacados tiene el cangrejo rojo' --phone=+340000004
php artisan nova:orchestrate-demo 'quiero una ruta sur desde playa blanca' --phone=+340000005
```

## 13. Riesgos y decisiones pendientes

### Riesgos

- Mezclar plataformas incorrectas por negocio.
- Dar cartas completas cuando debe darse resumen.
- Pedir datos de reserva en consultas solo informativas.
- No poder atribuir ventas físicas sin código o QR.
- No conciliar comisiones con ventas externas.
- Depender de MCPs externos sin fallback.

### Decisiones ya fijadas

- Taxilanz rutas/pedidos: WooCommerce MCP.
- Lanzaloe ventas online: Magento.
- Lanzaloe visitas/códigos: Laravel.
- Hoteles/códigos: Laravel externo.
- Nova: orquestación, WhatsApp, widget, IA, trazabilidad.
- La Cepa: restaurante de Bodega La Geria.
- Cangrejo Rojo: no dar carta completa; resumir y enlazar.

## 14. Próximo objetivo recomendado

Crear los negocios faltantes y ejecutar los seeds para poder probar el flujo completo con datos reales:

1. Crear Taxilanz.
2. Crear Lanzaloe.
3. Crear El Cangrejo Rojo.
4. Crear servicios WhatsApp/MCP correspondientes.
5. Probar conversaciones reales (seeds ya en DB).
6. Diseñar tabla de atribución/comisiones.
7. Empezar MCP WooCommerce Taxilanz y MCP Magento Lanzaloe.

---

## 17. Taxilanz MCP Server — Funcionalidades detalladas

El MCP Server de Taxilanz gestiona más de 180 hoteles conectados.

### Hoteles

- Listar hoteles conectados.
- Consultar hotel concreto.
- Actualizar estado de conexión.
- Ver estadísticas por hotel.

### Zonas

- Estadísticas por zona.
- Totales por zona.
- Filtros por Tías, Yaiza, Arrecife, Playa Blanca, etc.

### Reservas de taxi

- Crear reserva.
- Consultar reserva.
- Listar reservas.
- Cancelar reserva.

Datos de reserva:

- Teléfono del cliente.
- Nombre.
- Punto de recogida.
- Destino.
- Hotel.
- Fecha.
- Hora.
- Pasajeros.
- Método de pago.
- Puntos de recompensa.
- Recepcionista.

### Servicios recientes

- Últimos servicios de taxi.
- Filtros por zona.

### Conductores

- Conductores disponibles.
- Listado por estado.
- Integración prevista con Auriga.

### Mapa

- Marcadores de hoteles.
- Servicios activos.
- Localización.

### Estimaciones

- Precio estimado por ruta.
- Distancia.
- Moneda.

---

## 18. Hub de datos externos

Nova actúa no solo como bot conversacional sino como **hub de datos operativos** para plataformas externas.

Recursos Filament disponibles:

- Reservas externas (`NovaExternalBooking`).
- Pedidos externos (`NovaExternalOrder`).
- Pagos externos.
- Catálogo externo (`NovaExternalCatalogItem`).
- Fuentes externas.
- Logs de sincronización.

Casos de uso:

- Leer productos desde WooCommerce o Magento.
- Registrar reservas importadas.
- Crear pedidos.
- Sincronizar estados.
- Registrar logs de integración.
- Conectar datos con conversaciones y clientes.

---

## 19. Portal Taxista y sistema UI

El proyecto define un **Portal Taxista** con diseño propio:

- Mobile-first.
- Glass Dark UI.
- Estética SaaS premium.
- Basado en Filament, Livewire, Vite, Tailwind y Alpine.

Componentes UI definidos:

- Cards glass.
- Rows de listado.
- Badges.
- Botones CTA.
- Fondo con gradientes y grid sutil.
- Inputs glass.
- Estilo dark premium.

Funciones previstas del portal:

- Dashboard simplificado.
- Navegación por documentos.
- Acceso para taxistas.
- Solicitudes Nova.
- Command Palette / Spotlight.
- Documentación operativa.

---

## 20. Dominios funcionales

### Taxi Domain

- Taxistas, taxis, documentos, citas, tickets, gastos.
- Reservas, hoteles, servicios, conductores, localizaciones.

### HRM Domain

- Empleados, turnos, time off, asistencia, departamentos.

### Central Domain

- Departamentos, horarios, configuración de turnos, configuración global.

---

## 21. Event system y notificaciones

Nova usa eventos Laravel para desacoplar módulos.

Ejemplos de eventos previstos:

- `TaxiBookingCreated`
- `DocumentRequested`
- `TicketCreated`
- `EmployeeShiftAssigned`

Canales de notificación:

- WebSocket (tiempo real).
- Email.
- Notificaciones en base de datos.
- Push notifications (futuro).

---

## 22. Funcionalidades conversacionales futuras

### Contexto conversacional

Recordar conversaciones anteriores, preferencias, negocios visitados, patrones y reservas pasadas.

> "Como la última vez reservaste para 4, ¿es el mismo número esta vez?"

### Cambio de intención

Detectar frases como "en realidad prefiero…", "mejor…", "no, quiero…" y adaptar sin reiniciar.

### Upselling cruzado

- La Geria → Lanzaloe, Taxilanz.
- Lanzaloe → La Geria.
- Sirvo → La Geria.
- Taxilanz → La Geria, Sirvo, Lanzaloe.

> "¿Necesitas un taxi para llegar a la bodega?"

### Sugerencias proactivas

- Taxi después de cenar.
- Tour antes del aeropuerto.
- Producto relacionado tras una visita.

### Tono más humano

En vez de `"Falta la hora."` → `"¿A qué hora te viene bien?"`

### Detección de sentimiento

Adaptar tono según: positivo, negativo, urgente, indeciso, neutral.

---

## 23. Estado funcional resumido

La aplicación ya tiene base funcional para:

- Gestionar clientes Nova.
- Gestionar servicios activados.
- Configurar WhatsApp por negocio.
- Configurar MCP servers.
- Asociar perfiles de IA.
- Gestionar y editar knowledge IA desde Filament.
- Registrar solicitudes/conversaciones.
- Preparar integraciones con WooCommerce, Magento, WordPress, LatePoint, Auriga y Laravel externos.
- Operar recursos de taxi, hoteles, reservas y movilidad.
- Sincronizar entidades externas.
- Chat MCP por servidor con selección automática de tool.
- Editar prompts de IA desde Filament.
- Evolucionar hacia atribución y comisiones.

---

## 24. Descripciones del proyecto

### Descripción corta

Nova es una plataforma Laravel + Filament que actúa como hub operativo, conversacional e integrador para negocios turísticos y servicios locales de Lanzarote. Centraliza clientes, servicios, WhatsApp, IA, knowledge, reservas, taxis, comercios e integraciones externas mediante MCP, APIs y sincronizaciones.

### Descripción comercial

Nova es un sistema operativo digital para el ecosistema turístico local. Conecta taxis, bodegas, restaurantes, comercios, hoteles y servicios mediante una capa inteligente de IA, WhatsApp, widget web e integraciones MCP. La plataforma permite atender clientes, recomendar experiencias, gestionar reservas, activar ventas, sincronizar catálogos y medir la atribución comercial de cada operación.

### Descripción técnica

NovaHub MCP es una aplicación Laravel con paneles Filament que modela negocios, servicios, canales WhatsApp, perfiles IA, knowledge y MCP servers. Su arquitectura conecta empresas locales con plataformas externas como WooCommerce, Magento, WordPress, LatePoint, APIs propias y sistemas Laravel mediante una capa de orquestación conversacional. El flujo principal recibe mensajes desde WhatsApp o widget, detecta intención y negocio, consulta conocimiento, ejecuta integraciones MCP/API cuando aplica y registra solicitudes para seguimiento, reporting y atribución comercial.

---

## 15. Mapa de archivos clave ✨ nuevo

```text
app/
├── Services/
│   ├── NovaServicesPromptCatalog.php   ← catálogo de prompts editables
│   ├── NovaPromptLoader.php            ← lector de prompts con caché
│   └── Nova/
│       ├── NovaOrchestratorService.php ← punto de entrada principal
│       ├── NovaAiService.php           ← OpenAI: intent, extracción, respuesta
│       ├── NovaIntentExtractorService.php ← Ollama: extracción local
│       ├── NovaConversationDataExtractor.php ← combina ambos extractores
│       ├── NovaConversationContextService.php ← historial y patrones
│       ├── NovaKnowledgeService.php    ← fragments relevantes por keywords
│       ├── NovaCrossSellingService.php ← reglas de cross-selling
│       ├── NovaMcpClient.php           ← cliente REST + JSON-RPC para MCPs
│       ├── NovaMcpCreationService.php  ← crea reservas vía MCP
│       ├── NovaWhatsAppCloudService.php ← envía mensajes WhatsApp
│       └── NovaWebsiteKnowledgeImporter.php ← importa knowledge desde web
├── Livewire/
│   ├── ServerChat.php                 ← chat MCP por servidor en Filament
│   └── WorkflowPlanDisplay.php        ← visualización del plan de decisión
├── Filament/Resources/
│   ├── PromptResource/
│   │   └── Pages/ListPrompts.php      ← acciones Install/Reinstall Nova Prompts
│   └── ServerResource/
│       └── RelationManagers/
│           └── PromptsRelationManager.php
resources/views/livewire/
│   ├── server-chat.blade.php          ← UI del chat MCP
│   └── workflow-plan-display.blade.php
```

## 16. Dónde editar cada capa desde Filament

| Capa | URL Filament | Identificador |
|---|---|---|
| Prompt intent OpenAI | `/admin/prompts` | `nova-intent-detection` |
| Prompt extracción booking | `/admin/prompts` | `nova-booking-extraction` |
| Prompt respuesta conversacional | `/admin/prompts` | `nova-response-generation` |
| Prompt Ollama local | `/admin/prompts` | `nova-ollama-intent` |
| Reglas cross-selling | `/admin/prompts` | `nova-cross-selling-rules` |
| Instrucciones orquestador | `/admin/prompts` | `nova-orchestrator` |
| Prompts por servidor MCP | `/admin/servers/{id}` → Prompts | nombre libre |
| Herramientas por servidor | `/admin/servers/{id}` → Tools | — |
| Conocimiento por negocio | `/admin/nova-businesses/{id}/conocimiento-ia` | — |
| Conexiones MCP externas | `/admin/nova-businesses/{id}/mcp` | tipo: sirvo, la_geria… |
