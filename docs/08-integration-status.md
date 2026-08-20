## Estado real de integraciones MCP y servicios externos

Nova cuenta actualmente con una capa de integración operativa basada en MCP, APIs REST, sincronización directa, proyección a modelos internos, agentes Laravel AI SDK y observabilidad con AI Orbit.

## Auditoría de implantación frente a planes documentados

| Plan / área | Estado real | Evidencia en código | Pendiente principal |
|---|---|---|---|
| `unify-server-models.md` — `Server` como modelo único | **Parcial / prioridad alta** | `servers` ya tiene `nova_business_id`; `NovaBusiness::mcpServers()` apunta a `Server`; `Server` tiene relación con `NovaBusiness`; el panel de Nova muestra 3 servers y 11 tools activos. | `NovaMcpServer` todavía es un modelo separado sobre `nova_mcp_servers`, conserva `endpoint_url` y no es alias de `Server`; falta migrar datos, añadir scopes `local/forNova/active` completos y retirar referencias legacy. Esta duplicidad ya provoca confusión al auditar capabilities. |
| Arquitectura Filament-driven | **Parcial avanzado** | Existen modelos y recursos Filament para `NovaListingCategory`, `NovaIntentRule` y `NovaCrossSellingRule`; `AdminPanelProvider` descubre estos recursos. | `NovaCrossSellingService` sigue usando reglas hardcoded porque la consulta configurada está comentada; todavía quedan heurísticas hardcoded en el orquestador/data extractor. |
| `NovaBusiness` como raíz | **Parcial avanzado** | `NovaBusiness` tiene relaciones con services, MCP servers, AI profiles, knowledge, listing categories, cross-selling, intent rules, bookings externas, catálogo e integraciones. | Completar relación operativa de `NovaRequest` por `nova_business_id`, normalizar `Server`/`NovaMcpServer` y consolidar historial conversacional. |
| Knowledge IA semántico | **Implementado** | `NovaKnowledgeEmbedder` usa `Laravel\Ai\Embeddings`; `NovaAiKnowledge` guarda `embedding` y `vectorized_at`; `NovaKnowledgeService` hace ranking semántico con fallback keyword. | Activar proveedor de embeddings real en entorno y vectorizar datos existentes. |
| Laravel AI SDK para IA conversacional | **Implementado en el core Nova** | `NovaIntentAgent`, `NovaBookingExtractionAgent` y `NovaResponseAgent`; `NovaAiService` delega en agentes SDK con provider/model/failover. | Convertir más lógica de orquestación a agentes con tools y memoria; eliminar heurísticas residuales donde tenga sentido. |
| AI Orbit | **Instalado y operativo para observabilidad** | `config/ai-orbit.php`, rutas `/ai-orbit/*`, tablas Orbit y tablas SDK `agent_conversations`/`agent_conversation_messages`. | Usarlo sistemáticamente para evaluar prompts/agentes, almacenar runs y comparar modelos; no sustituye al entrenamiento/fine-tuning de modelos. |
| `/ai-bot` / Chat Gateway | **Operativo parcial avanzado** | `NovaChatController` llama a `NovaOrchestratorService`; el orquestador usa `NovaAiService`, knowledge, contexto, MCP clients y cross-selling. | Conectar un agente conversacional dedicado con memoria SDK/tools o adaptar `filament-agentic-chatbot` como canal runtime. |
| Filament Agentic Chatbot | **Implementado como capa RAG/workflow separada** | `config/filament-agentic-chatbot.php` define modelos, providers, vector backend, parent agent y workflow actions. | Integrar explícitamente workflows Nova con agentes Laravel AI SDK/AI Orbit o enrutar el bot hacia `/api/nova/chat` según canal. |

### Servers MCP registrados

| Server | Endpoint | Estado |
|---|---|---|
| Nova MCP | `/mcp/nova` | Operativo: 9 tools activas (`info`, `list_agents`, `list_servers`, `get_server_tools`, `call_server`, `list_capabilities`, `list_sync_connectors`, `invoke_agent`, `tools`) |
| Nova Conversation Core | `/mcp/nova-conversation-core` | Registrado bajo negocio Nova, sin tools activas actualmente |
| PromptlyAgent Agent Server | `/mcp/promptly-agent` | Operativo inicial: 2 tools activas (`list_agents`, `invoke_agent`) |
| Sirvo Restaurants MCP | `/mcp/sirvo-restaurantes` | Operativo inicial |
| La Geria Shop+Tours MCP | `/mcp/la-geria-wordpress-woo-latepoint` | Operativo avanzado |
| Taxilanz Rutas Woo MCP | `/mcp/taxilanz-rutas-woo` | Operativo inicial |
| Taxilanz Hoteles Laravel MCP | `/mcp/taxilanz-hoteles-laravel` | Operativo inicial |
| Lanzaloe Magento MCP | `/mcp/lanzaloe-magento` | Operativo inicial/medio |
| Taxilanz Chauffeur Booking MCP | `/mcp/taxilanz-chauffeur-booking` | Operativo avanzado |
| Aureuserp | `/mcp-aureuserp` | Registrado, pendiente de capacidades |

### Capa MCP

Estado: **operativo avanzado**.

Capacidades:

- Registro dinámico de servers MCP.
- Endpoints web MCP.
- Cliente MCP genérico.
- Negocio Nova con 3 MCP servers asociados y 11 tools activas en el panel de cliente.
- JSON-RPC `tools/list` y `tools/call`.
- Health checks.
- Autenticación por token/API key.
- Registro de errores y último check.
- Sync local y full sync desde el panel.

### La Geria

Estado: **operativo avanzado**.

Capacidades:

- WordPress/WooCommerce/LatePoint.
- Sync de productos Woo.
- Sync de servicios LatePoint.
- Sync de reservas LatePoint.
- Sync de pedidos Woo como bookings.
- Sync de clientes y transacciones.
- Creación remota de reservas LatePoint.
- Materialización local de reservas creadas.
- Proyección a tours y bookings internos.

Pendiente:

- Validar flujo completo en producción.
- Conectar pagos y atribución.
- Optimizar disponibilidad en `/explore`.

### Sirvo

Estado: **operativo inicial/medio**.

Capacidades:

- MCP registrado.
- Sync de restaurantes/branches.
- Sync de reservas.
- Consulta de capacidad.
- Creación remota de reservas desde solicitudes públicas.
- Proyección a reservas internas.

Pendiente:

- Validar endpoints reales.
- Mejorar disponibilidad multi-restaurante.
- Conectar `/explore` y reporting.

### Taxilanz

Estado: **operativo parcial avanzado**.

Capacidades:

- MCP de rutas Woo.
- MCP hoteles Laravel.
- MCP Chauffeur Booking.
- Sync de rutas chauffeur.
- Sync de reservas chauffeur.
- Sync de servicios de taxi.
- Herramientas para hoteles, zonas, bookings, conductores, mapa, recepcionistas, comisiones, payouts y estimaciones.
- Comisiones de recepcionistas parcialmente operativas.
- Booking taxi local vía MCP.

Pendiente:

- Sustituir simulación Auriga por integración real.
- Unificar rutas/taxi/chauffeur.
- Conectar `/explore`.
- Completar reporting por hotel/zona/recepcionista.
- Conectar atribución global Nova.

### Lanzaloe Magento

Estado: **operativo inicial/medio**.

Capacidades:

- MCP Magento registrado.
- Sync de productos Magento.
- Sync de pedidos Magento.
- Wrapper para crear pedidos Magento.
- Consulta de producto por SKU.
- Búsqueda de productos.
- Operaciones bulk.

Pendiente:

- Validar credenciales reales.
- Conectar catálogo a `/explore`.
- Activar compras reales.
- Conectar comisión online.
- Conectar visitas/códigos físicos vía Laravel externo.

### `/explore`

Estado: **operativo parcial con creación remota**.

Capacidades:

- Capa pública visual.
- Solicitudes públicas de reserva.
- Consulta de disponibilidad.
- Pago Redsys.
- Creación remota de reservas Sirvo.
- Creación remota de reservas LatePoint.
- Materialización local de reservas remotas.

Pendiente:

- Marketplace visual completo.
- Catálogo real por negocio.
- Checkout unificado.
- Integración Magento/Woo para compras.
- Atribución y comisiones.
- Tracking UTM/QR/source.
- Backoffice de conversiones.

### Redsys

Estado: **técnicamente implementado / pendiente integración total**.

Capacidades:

- Payload redirect.
- Firma HMAC SHA256 V1.
- Verificación de firma.
- Decodificación merchant params.
- Detección de respuesta OK/KO.

Pendiente:

- Unificar flujos.
- Conciliar pagos.
- Relacionar pago con reserva remota.
- Integrar con reporting y comisiones.

### IA conversacional

Estado: **operativo parcial avanzado / migrado al Laravel AI SDK en el core**.

Capacidades:

- Detección de intención con `NovaIntentAgent`.
- Extracción de datos de reserva con `NovaBookingExtractionAgent`.
- Generación de respuesta natural con `NovaResponseAgent`.
- Configuración de provider/model/failover en `config/nova_ai.php`.
- Observabilidad de llamadas SDK mediante AI Orbit cuando `AI_ORBIT_OBSERVABILITY_ENABLED=true`.
- Contexto por teléfono.
- Preferencias y patrones.
- Cross-selling.
- Transcripción de audios WhatsApp.
- Knowledge por negocio con embeddings y búsqueda semántica.
- Tests focalizados para `NovaAiService` y `NovaKnowledgeService`.

Pendiente:

- Handoff humano.
- Botones WhatsApp.
- Persistencia conversacional más estructurada usando `agent_conversations` o la memoria propia de `filament-agentic-chatbot`.
- Métricas de calidad operativas y revisión periódica en AI Orbit.
- Pasar cross-selling y más heurísticas residuales a reglas configuradas o tools/agentes.

### AI Orbit y entrenamiento de agentes

Estado: **operativo para observabilidad, sandbox y optimización de agentes; no es entrenamiento/fine-tuning por sí solo**.

Qué permite ahora:

- Ver ejecuciones/runs de agentes Laravel AI SDK si pasan por el SDK oficial.
- Inspeccionar payloads, coste, proveedor/modelo y errores.
- Comparar prompts/modelos en Prompt Lab.
- Usar Agent Sandbox para probar agentes descubiertos en `app/Ai/Agents`.
- Usar conversaciones SDK si el agente implementa memoria (`Conversational`/`RemembersConversations`) y existen `agent_conversations` y `agent_conversation_messages`.

Qué no hace directamente:

- No “entrena” pesos de un modelo local o remoto.
- No sustituye fine-tuning del proveedor.
- No convierte automáticamente un workflow de `filament-agentic-chatbot` en un agente Laravel AI SDK.

Cómo se usa para mejorar un agente:

1. Definir o ajustar el agente en `app/Ai/Agents`.
2. Probarlo desde `/ai-orbit/playground` o `/ai-orbit/prompt-lab`.
3. Revisar runs, errores, coste y calidad en `/ai-orbit/runs`.
4. Ajustar instrucciones, temperatura, modelo, tools o knowledge.
5. Usar ese agente desde `/api/nova/chat`, `/ai-bot`, WhatsApp o workflows del chatbot.

Uso con `ai-chatbot` / Filament Agentic Chatbot:

- Sí puede usarse en conjunto.
- Opción actual: `filament-agentic-chatbot` sigue siendo una capa RAG/workflow separada con sus propios bots, sources, chunks, workflows y memoria.
- Integración recomendada: crear workflow actions que llamen a `/api/nova/chat` o a servicios Nova (`NovaAiService`, `NovaKnowledgeService`, `NovaMcpClient`) para reutilizar la inteligencia central.
- Alternativa avanzada: crear un agente Laravel AI SDK específico para el chatbot con tools (`search-nova-knowledge`, `call-mcp-tool`, `list-mcp-tools`) y observarlo con AI Orbit.

### Workflow Actions (Filament Agentic Chatbot)

Estado: **implementado**.

Acciones PHP registradas en `config/filament-agentic-chatbot.php` bajo `workflow.actions`:

| actionKey | Clase | Descripción |
|---|---|---|
| `search-nova-knowledge` | `Actions\Workflow\SearchNovaKnowledgeAction` | Busca en `nova_ai_knowledge` (fuente única, sin duplicación) |
| `call-mcp-tool` | `Actions\Workflow\CallMcpToolAction` | Llama una tool de cualquier Server MCP local via JSON-RPC |
| `list-mcp-tools` | `Actions\Workflow\ListMcpToolsAction` | Lista tools activas de un negocio o servidor |

Workflow examples listos para importar en `docs/nova-workflow-examples/`:

- `la-geria-agent.json` — La Geria LatePoint (visitas y servicios)
- `sirvo-agent.json` — Sirvo (restaurante + reservas)
- `taxilanz-agent.json` — Taxilanz (hoteles + servicios, con collectInput)
- `lanzaloe-agent.json` — Lanzaloe (productos Magento)

Ver referencia completa en `docs/nova-workflow-actions.md`.
