# NOVA – Estado Actual y Visión Global

> Última actualización: junio 2026
> Estado: **Fase 2 inicial — consolidando `/explore` como capa visual de reservas, compras y conversión comercial**
[01-project-vision.md](01-project-vision.md)
[02-technical-spec.md](02-technical-spec.md)
[03-ui-system.md](03-ui-system.md)
[04-engineering-rules.md](04-engineering-rules.md)
[05-nova-ai-mcp-architecture.md](05-nova-ai-mcp-architecture.md)
[06-integration-solutions-comparison.md](06-integration-solutions-comparison.md)
[07-whatsapp-flow-improvements.md](07-whatsapp-flow-improvements.md)
[08-integration-status.md](08-integration-status.md)
[configurar-casos-uso-reales-filament.md](configurar-casos-uso-reales-filament.md)
[crear-workflow-mcp-filament.md](crear-workflow-mcp-filament.md)
[EXPLORE_ANALYSIS.md](EXPLORE_ANALYSIS.md)
[flujo-completo-nova-agentic-chatbot.md](flujo-completo-nova-agentic-chatbot.md)
[INFRASTRUCTURE_DIAGRAM.md](INFRASTRUCTURE_DIAGRAM.md)
[jerarquia-nova-mcp-servers.md](jerarquia-nova-mcp-servers.md)
[mcp-server-taxilanz.md](mcp-server-taxilanz.md)
[migrar-canales-recursos-nova.md](migrar-canales-recursos-nova.md)
[Nova Architecture and Integrations.md](Nova%20Architecture%20and%20Integrations.md)
[Nova External Sync Integration.md](Nova%20External%20Sync%20Integration.md)
[nova-agentic-chatbot-integration.md](nova-agentic-chatbot-integration.md)
[nova-business-architecture.md](nova-business-architecture.md)
[nova-filament-driven-architecture.md](nova-filament-driven-architecture.md)
[Nova-MCP-External-Integration-Guide.md](Nova-MCP-External-Integration-Guide.md)
[nova-workflow-actions.md](nova-workflow-actions.md)
[NOVA_ARCHITECTURE_MASTER.md](NOVA_ARCHITECTURE_MASTER.md)
[NOVA_MASTER.md](NOVA_MASTER.md)
---

## Stack técnico

- PHP 8.4 / Laravel 12
- Filament v5 (Admin + App + Portal)
- Livewire 4 + Flux v2
- Tailwind CSS v4 / Alpine.js v3
- Reverb (WebSockets)
- Traccar + snapshot local (tracking)
- Redsys (pagos)
- MCP servers propios y externos

---

## Estado global por área

| Área | Estado |
|---|---|
| Portal Taxista | Operativo parcial / avanzado |
| App Staff | Operativo parcial / avanzado |
| Clientes Nova (NovaBusiness) | Operativo |
| Servicios Nova (NovaService) | Operativo |
| MCP Servers | Operativo parcial |
| WhatsApp | Operativo parcial |
| IA / Knowledge | Operativo parcial |
| `/explore` | Operativo inicial |
| Redsys | Operativo parcial |
| Listing Config (nova_listing_categories) | Implementado — pendiente conectar servicio |
| Cross-selling (nova_cross_selling_rules) | Implementado — pendiente conectar servicio |
| Intent Rules (nova_intent_rules) | Implementado — pendiente conectar servicio |
| Reservas end-to-end | Pendiente / parcial |
| Compras end-to-end | Pendiente |
| Atribución y comisiones | Pendiente |
| Marketplace visual `/explore` | Pendiente / en construcción |
| Integraciones externas completas | Pendiente / parcial |

---

## Negocios contemplados

### La Geria
- Bodega, visitas guiadas, wine tours, Taberna La Cepa, eventos
- Estado: **operativo parcial** — knowledge sembrado, detección contextual activa
- Pendiente: MCP creaciones WordPress/WooCommerce/LatePoint, reservas end-to-end

### Lanzaloe
- Comercio, productos aloe vera, venta online, visitas, códigos, atribución física
- Estado: **pendiente / preparado** — knowledge preparado, arquitectura definida
- Comisión: 20% online, 10% física con visita atribuida
- Pendiente: crear NovaBusiness, conectar Magento, catálogo en `/explore`

### Taxilanz
- Taxi, traslados, excursiones, rutas, hoteles, reservas, MCP propio, WooCommerce, Auriga API
- Estado: **pendiente / preparado parcialmente** — knowledge preparado, MCP documentado, portal taxi operativo
- **Pendiente urgente**: crear NovaBusiness Taxilanz → seed listing category hotel

### Sirvo
- Restaurante, reservas, fallback genérico
- Estado: **operativo parcial** — MCP activo, seed ejecutado, fallback activo
- Pendiente: MCP Next.js para creaciones, reservas reales

### El Cangrejo Rojo
- Restaurante, información, reservas
- Estado: **pendiente / preparado** — knowledge preparado
- Regla: nunca dar la carta completa, resumir y enlazar
- Pendiente: crear NovaBusiness, seed, WhatsApp/MCP

---

## Canales principales

### WhatsApp
Estado: **operativo parcial**

Operativo: orquestación base, detección de intención, knowledge por negocio, registro solicitudes, respuestas comerciales compactas.

Pendiente: conversación más natural, upselling cruzado completo, handoff humano, botones interactivos, persistencia estructurada de estado conversacional.

### `/explore`
Estado: **operativo inicial**

Operativo: ruta pública, consulta lugares/disponibilidad, solicitudes públicas, Redsys start/notify/ok/ko.

Pendiente: UI marketplace, catálogo real, checkout unificado, cross-selling visual, tracking UTM/QR, atribución completa.

### Filament (App Panel)
Estado: **operativo parcial**

Operativo: recursos Nova base, gestión servicios/WhatsApp/MCP/IA/Knowledge por cliente, subnavegación por cliente.

Pendiente: panel estado MCP, conversaciones recientes, ventas/leads, vista funnel conversación→solicitud→pago→comisión.

---

## Trabajo realizado — sesión junio 2026

### Arquitectura Filament dirigida por DB
- **3 nuevas tablas** reemplazan 65+ `str_contains` hardcoded en servicios:
  - `nova_listing_categories` — configura qué tool MCP se llama y cómo se presenta cada tipo de listado
  - `nova_cross_selling_rules` — reglas de sugerencia entre negocios
  - `nova_intent_rules` — keywords de detección de intent (global y por business)
- `nova_business_id` añadido a `servers` y `nova_requests`
- Seeders con datos migrados desde código hardcoded
- **3 Filament Resources** Blueprint-compliant + UX-auditados:
  - `NovaListingCategoryResource` (slug=Select, keywords=TagsInput, tool_params=KeyValue)
  - `NovaCrossSellingRuleResource` (trigger_intent=Select, is_active=ToggleButtons)
  - `NovaIntentRuleResource` (rule_type=ToggleButtons, intent_key=Select)

### Documentación actualizada
- `docs/plans/nova-filament-driven-architecture.md` — arquitectura completa DB-driven
- `docs/plans/unify-server-models.md` — plan unificación Server + NovaMcpServer
- `docs/plans/nova-business-architecture.md` — árbol completo de relaciones por NovaBusiness

### Pendiente fase actual
- Conectar `buildLiveListingReply()` a `nova_listing_categories` (elimina isHotel/isVisit/isRestaurant)
- Conectar `NovaCrossSellingService` a `nova_cross_selling_rules`
- Conectar `NovaConversationDataExtractor` a `nova_intent_rules`
- Crear NovaBusiness para Taxilanz, Lanzaloe, El Cangrejo Rojo
- Ejecutar seeds de listing categories hotel tras crear Taxilanz

---

## Roadmap próximos pasos

1. Conectar servicios a las nuevas tablas DB (Fase 4 del plan de arquitectura)
2. Crear negocios faltantes en Filament
3. Unificar Server + NovaMcpServer (ver `docs/plans/unify-server-models.md`)
4. Consolidar `/explore` como marketplace visual
5. Diseñar tabla de atribución comercial
6. Probar flujo completo: WhatsApp → `/explore` → pago → Filament → comisión


## Notas de implementación — Portal y App

### Portal Taxista
- UI: dark glass, mobile-first, Filament `portal` + Livewire
- CSS desde `resources/css/portal.css` via `PortalPanelProvider`; fondo por RenderHook `BODY_START`
- Estado: Dashboard + Spotlight + Documentos operativos; transiciones stack/slide pendientes
- Riesgo: si Vite no recompila (permisos `public/build`) el portal puede verse sin estilos

### App Staff
- UI: light, desktop — CSS en `resources/css/filament/app/theme.css`
- Taxistas: tabla optimizada + subnavegación (Taxis/Citas/Documentos/Chat)
- Documentos: subida multipágina por tipos (Agencias/Cuotas/Nominas/Repuestos/Seguros)
- `app/locations`: tracking moderno, sidebar light, slide-overs para info/replay

### Tracking
- Taxistas envían posición a Traccar `5055` via `tracking_uuid`; snapshot local como fallback
- Reverb: proxy WebSocket por `443` hacia `127.0.0.1:9001`
- Replay: prioridad Traccar, fallback tabla `positions` local
- Retención: `positions` podada a 7 días con `tracking:prune-positions`

---

## Project Operating System

Toda la lógica operativa vive en `docs/`:

```
docs/
  01-project-vision.md              <- visión, capas y fases
  02-technical-spec.md              <- arquitectura técnica
  03-ui-system.md                   <- sistema visual
  04-engineering-rules.md           <- reglas de desarrollo
  NOVA_MASTER.md                    <- estado actual (este archivo)
  08-integration-status.md          <- estado MCP y servidores
  filament-blueprint/SKILL.md       <- proceso Blueprint Filament
  filament-forms-ux-audit/SKILL.md  <- auditoría UX formularios
  plans/
    nova-filament-driven-architecture.md
    nova-business-architecture.md
    unify-server-models.md
```

Agentes: leer `AGENTS.md` antes de cualquier tarea.
