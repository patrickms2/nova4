# Nova – Visión del Proyecto

## 1. Visión

Nova es un sistema operativo digital para conectar movilidad, comercio, turismo y servicios locales.

Motor inicial: **Taxi + Comercios locales + Servicios turísticos en Lanzarote**

Nova conecta taxis, taxistas, hoteles, bodegas, restaurantes, comercios, servicios turísticos, turistas y residentes mediante WhatsApp, widget web, `/explore`, paneles Filament, MCP servers, APIs externas y plataformas como WooCommerce, Magento, WordPress, LatePoint y sistemas Laravel externos.

El objetivo no es sustituir las plataformas existentes, sino actuar como capa central de orquestación, operación, venta, reserva, recomendación y trazabilidad.

Nova debe poder **informar, recomendar, reservar, vender, cobrar, consultar disponibilidad, conectar con MCPs/APIs, registrar solicitudes, medir atribución y calcular comisiones**.

---

## 2. Capas del producto

```
WhatsApp / Widget
→ capa conversacional

/explore
→ capa visual pública de reservas, compras y descubrimiento

Filament
→ capa interna de operación y administración

MCP / APIs / Sync
→ capa de integración con sistemas externos

Nova IA / Knowledge
→ capa de inteligencia, recomendación y contexto

Nova Requests / Public Booking Requests
→ capa de registro operativo y trazabilidad

Redsys / pagos externos
→ capa de monetización y cobro
```

---

## 3. Ciclo completo

```
Descubrimiento
→ conversación
→ recomendación
→ reserva
→ compra
→ pago
→ operación
→ integración externa
→ atribución
→ comisión
```

---

## 4. Arquitectura de datos (NovaBusiness como raíz)

Cada cliente (`NovaBusiness`) es la raíz de todo su ecosistema:

```
NovaBusiness
├── NovaService (flags: has_whatsapp, has_mcp, has_sales...)
├── Server → Tools / Resources / Prompts  ← MCP
├── NovaAiKnowledge                        ← knowledge base
├── NovaExternalBooking                    ← reservas externas
├── NovaExternalOrder                      ← pedidos externos
├── NovaExternalTransaction                ← pagos externos
├── NovaExternalCatalogItem                ← catálogo
├── NovaExternalCustomer                   ← clientes externos
├── NovaIntegrationSetting + SyncLog       ← integraciones
├── NovaListingCategory                    ← config de listados IA
├── NovaCrossSellingRule                   ← reglas cross-selling
├── NovaIntentRule                         ← reglas de intent (por business)
├── NovaWhatsappChannel + Messages         ← WhatsApp
├── NovaAiProfile                          ← agentes IA
└── NovaRequest                            ← conversaciones
```

Ver plan detallado en `docs/plans/nova-filament-driven-architecture.md`.

---

## 5. Fases del proyecto

### Fase 1 – Producto real
**Estado: completada en su base, con pulido pendiente.**

- Portal Taxista (dark glass, mobile-first)
- App Staff (light, desktop)
- Command Palette / Spotlight
- Nova Requests
- Tracking taxi
- Gestión base de clientes/servicios

### Fase 2 – Motor económico real
**Estado: en inicio / pendiente prioritario.**

- `/explore` como capa visual de conversión
- Reservas reales end-to-end
- Compras reales end-to-end
- Pagos Redsys
- Atribución comercial
- Comisiones
- Integraciones MCP/API (WooCommerce, Magento, Auriga, LatePoint)
- Reporting mensual

Prioridades:
1. Crear negocios faltantes (Taxilanz, Lanzaloe, El Cangrejo Rojo)
2. Consolidar `/explore` como marketplace visual
3. Conectar availability real por negocio
4. Diseñar tabla de atribución comercial
5. Probar flujo completo: WhatsApp → `/explore` → pago → Filament → comisión

### Fase 3 – Ecosistema
**Estado: futuro.**

- CanaryClick
- IA contextual avanzada con embeddings
- Red de negocios conectados
- Economía circular
- Wallet/incentivos

---

## 6. Frase operativa actual

```
Nova · Fase 2 inicial · consolidando /explore como capa visual de reservas, compras y conversión comercial.
```

---
