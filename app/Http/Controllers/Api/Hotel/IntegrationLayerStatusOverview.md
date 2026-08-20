## Estado real de integraciones MCP y servicios externos

Nova cuenta actualmente con una capa de integración operativa basada en MCP, APIs REST, sincronización directa y proyección a modelos internos.

### Servers MCP registrados

| Server | Endpoint | Estado |
|---|---|---|
| Nova | `/mcp/nova` | Registrado, sin tools activas |
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

Estado: **operativo parcial avanzado**.

Capacidades:

- Detección de intención con IA y fallback.
- Extracción de datos de reserva.
- Generación de respuesta natural.
- Contexto por teléfono.
- Preferencias y patrones.
- Cross-selling.
- Transcripción de audios WhatsApp.
- Knowledge por negocio.

Pendiente:

- Handoff humano.
- Botones WhatsApp.
- Persistencia conversacional más estructurada.
- Tests.
- Métricas de calidad.
