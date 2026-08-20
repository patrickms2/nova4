# Cuadro Comparativo de Soluciones de Integración

## Estado Actual y Pendientes por Negocio

| Negocio | Plataforma | Tipo Integración | Estado Sync/Lectura | Estado Creaciones | Solución Actual | Solución Objetivo | Pendiente |
|---------|-----------|-----------------|---------------------|-------------------|-----------------|------------------|-----------|
| **La Geria** | WordPress | MCP (WordPress MCP) | ✅ Completado (NovaWooLatePointDatabaseSyncService) | ❌ Pendiente | DB directa | MCP estándar | Implementar cliente MCP para creaciones |
| **La Geria** | WooCommerce | MCP (WooCommerce MCP) | ✅ Completado (NovaWooCommerceApiSyncService) | ❌ Pendiente | API REST directa | MCP estándar | Implementar cliente MCP para creaciones |
| **La Geria** | LatePoint | MCP (LatePoint MCP) | ✅ Completado (NovaWooLatePointDatabaseSyncService) | ❌ Pendiente | DB directa | MCP estándar | Implementar cliente MCP para creaciones |
| **Taxilanz** | WooCommerce | MCP (WooCommerce MCP) | ✅ Completado (NovaWooCommerceApiSyncService) | ❌ Pendiente | API REST directa | MCP estándar | Implementar cliente MCP para creaciones |
| **Taxilanz** | Auriga API | API REST propia | ❌ Pendiente | ❌ Pendiente | - | API REST directa | Implementar servicio Auriga API |
| **Taxilanz** | Laravel Visitas | Laravel app | ❌ Pendiente | ❌ Pendiente | - | Laravel MCP | Crear MCP server para visitas/códigos |
| **Lanzaloe** | Magento2 | MCP (Magento2 MCP) | ✅ Completado (NovaMagentoApiSyncService) | ✅ Completado (LaragentoWrapper) | API REST directa | LaragentoWrapper + MCP | Extender magento2-mcp con create_order |
| **Lanzaloe** | Laravel Visitas | Laravel app | ❌ Pendiente | ❌ Pendiente | - | Laravel MCP | Crear MCP server para visitas/códigos |
| **Sirvo** | Next.js | MCP (por hacer) | ❌ Pendiente | ❌ Pendiente | - | MCP estándar | Crear MCP server Next.js |
| **Nova** | - | MCP Server propio | ✅ Completado (TaxilanzMCPServer) | ✅ Completado | MCP propio | MCP propio | - |

## Detalle por Tipo de Integración

### 1. MCP Estándar (Recomendado para creaciones)

**Ventajas:**
- Protocolo estandarizado
- Tools bien definidas
- Fácil integración con IA
- Schema validation

**Implementado:**
- ✅ TaxilanzMCPServer (propio de Nova)
- ✅ NovaMcpClient (cliente genérico)
- ✅ NovaMcpCreationService (servicio creaciones)

**Pendiente:**
- ❌ Cliente MCP para WordPress/WooCommerce/LatePoint
- ❌ Extensión magento2-mcp con create_order
- ❌ MCP server Sirvo Next.js
- ❌ MCP server Laravel visitas/códigos

### 2. DB Directa (Sync/Lectura rápida)

**Ventajas:**
- Sync rápido de grandes volúmenes
- Cache local
- Sin dependencia de API externa

**Implementado:**
- ✅ NovaWooLatePointDatabaseSyncService (WordPress/WooCommerce/LatePoint)

**Pendiente:**
- ❌ Sync Laravel visitas/códigos

### 3. API REST Directa (Creaciones)

**Ventajas:**
- Control total
- Sin dependencia de MCP externo
- Fácil debugging

**Implementado:**
- ✅ NovaMagentoApiSyncService (Magento)
- ✅ LaragentoWrapper (abstracción Magento)
- ✅ SirvoReservationClient (Sirvo)

**Pendiente:**
- ❌ Auriga API client (Taxilanz)

### 4. Híbrida (Sync DB + Creaciones MCP/API)

**Arquitectura recomendada:**
```
Sync/Lectura → DB directa o API REST
Creaciones → MCP estándar o API REST con wrapper
```

**Implementado:**
- ✅ La Geria: DB sync (WooCommerce/LatePoint) + MCP creaciones (pendiente)
- ✅ Lanzaloe: API sync (Magento) + LaragentoWrapper creaciones
- ✅ Taxilanz: API sync (WooCommerce) + MCP creaciones (pendiente)

## Resumen de Pendientes por Prioridad

### Alta Prioridad (Bloqueantes para creaciones)

1. **Cliente MCP para WordPress/WooCommerce/LatePoint**
   - Implementar herramientas MCP en NovaMcpClient
   - Integrar con NovaMcpCreationService
   - Probar con La Geria

2. **Extender magento2-mcp con create_order**
   - Añadir herramienta create_order en mcp-server.js
   - Validar con Lanzaloe
   - Documentar uso

3. **Auriga API client (Taxilanz)**
   - Crear servicio TaxilanzAurigaClient
   - Implementar booking taxi
   - Integrar con flujo WhatsApp

### Media Prioridad (Mejoras)

4. **MCP server Sirvo Next.js**
   - Crear servidor MCP en Next.js
   - Exponer herramientas de reservas
   - Integrar con Nova

5. **MCP server Laravel visitas/códigos**
   - Crear MCP server para Lanzaloe visitas
   - Crear MCP server para Taxilanz visitas
   - Integrar con sistema de puntos

### Baja Prioridad (Optimizaciones)

6. **Sync Laravel visitas/códigos**
   - Implementar sync de visitas
   - Implementar sync de códigos
   - Integrar con NovaExternalBookings

## Configuración Requerida en DB

### nova_mcp_servers

Para cada negocio, registrar:

**La Geria:**
```php
[
    'nova_business_id' => 1,
    'nova_service_id' => 1,
    'name' => 'La Geria WordPress MCP',
    'type' => 'wordpress',
    'endpoint_url' => 'https://lageria.novagestion.eu',
    'status' => 'active',
    'credentials' => [
        'token' => 'wp-mcp-token',
    ],
]
```

**Taxilanz:**
```php
[
    'nova_business_id' => 2,
    'nova_service_id' => 2,
    'name' => 'Taxilanz WooCommerce MCP',
    'type' => 'woocommerce',
    'endpoint_url' => 'https://taxilanz.com',
    'status' => 'active',
    'credentials' => [
        'consumer_key' => 'ck_xxx',
        'consumer_secret' => 'cs_xxx',
    ],
]
```

**Lanzaloe:**
```php
[
    'nova_business_id' => 3,
    'nova_service_id' => 3,
    'name' => 'Lanzaloe Magento MCP',
    'type' => 'magento',
    'endpoint_url' => 'https://lanzaloe.novagestion.eu/rest/V1',
    'status' => 'active',
    'credentials' => [
        'base_url' => 'https://lanzaloe.novagestion.eu/rest/V1',
        'api_token' => 'magento-api-token',
    ],
]
```

**Sirvo:**
```php
[
    'nova_business_id' => 4,
    'nova_service_id' => 4,
    'name' => 'Sirvo MCP',
    'type' => 'sirvo',
    'endpoint_url' => 'https://sirvo.novagestion.eu',
    'status' => 'active',
    'credentials' => [
        'token' => 'sirvo-api-token',
    ],
]
```

## Próximos Pasos Recomendados

1. **Ejecutar migración** nova_magento_sync_logs
2. **Probar LaragentoWrapper** con Magento de Lanzaloe
3. **Implementar cliente MCP** para WordPress/WooCommerce/LatePoint
4. **Extender magento2-mcp** con create_order
5. **Registrar MCP servers** en DB para cada negocio
6. **Probar creaciones** vía MCP en flujo WhatsApp
