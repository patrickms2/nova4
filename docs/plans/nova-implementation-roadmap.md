# Nova — Plan de Implementación Priorizado

> **Versión**: 1.0
> **Fecha**: 18 Junio 2026
> **Estado**: Planificación

---

## Visión General

Este plan organiza la implementación de la arquitectura Nova en fases priorizadas, basándose en la arquitectura maestra definida en `docs/NOVA_ARCHITECTURE_MASTER.md`.

**Principio guía**: Cada fase debe ser completada y probada antes de pasar a la siguiente. Las fases están diseñadas para entregar valor incrementalmente.

---

## Fase 1: Normalización de Respuestas (Prioridad ALTA)

**Objetivo**: Implementar el servicio de normalización para que Nova MCP pueda convertir respuestas de MCP externos a estructura de Nova.

**Tiempo estimado**: 2-3 días

### Tareas

- [ ] **1.1 Crear NovaResponseNormalizer**
  - Archivo: `app/Services/Nova/NovaResponseNormalizer.php`
  - Métodos:
    - `normalizeBookingResponse(array $response, Server $server): array`
    - `normalizeOrderResponse(array $response, Server $server): array`
    - `normalizeTransactionResponse(array $response, Server $server): array`
  - Lógica específica por tipo de MCP:
    - Sirvo (restaurantes)
    - LatePoint (visitas bodega)
    - Magento (eCommerce)
    - WooCommerce (eCommerce)
    - Taxilanz (taxi)

- [ ] **1.2 Escribir tests para NovaResponseNormalizer**
  - Archivo: `tests/Feature/NovaResponseNormalizerTest.php`
  - Tests:
    - Normalizar respuesta Sirvo booking
    - Normalizar respuesta LatePoint booking
    - Normalizar respuesta Magento order
    - Normalizar respuesta WooCommerce order
    - Normalizar respuesta transaction

- [ ] **1.3 Integrar en NovaOrchestratorService**
  - Modificar: `app/Services/Nova/NovaOrchestratorService.php`
  - Añadir llamada a normalizador después de ejecutar tool MCP
  - Verificar que la normalización funciona en flujo real

**Criterio de éxito**: Tests pasan, normalización funciona para al menos 2 tipos de MCP (Sirvo y LatePoint).

---

## Fase 2: Registro en Filament (Prioridad ALTA)

**Objetivo**: Implementar el servicio de registro para que Nova MCP pueda guardar datos normalizados en tablas de Nova.

**Tiempo estimado**: 2-3 días

### Tareas

- [ ] **2.1 Crear NovaRegistrationService**
  - Archivo: `app/Services/Nova/NovaRegistrationService.php`
  - Métodos:
    - `registerBooking(array $normalized, Server $server, string $intent): NovaExternalBooking`
    - `registerOrder(array $normalized, Server $server): NovaExternalOrder`
    - `registerTransaction(array $normalized, Server $server): NovaExternalTransaction`
  - Lógica:
    - Verificar duplicados (external_id + source)
    - Preservar source attribution
    - Manejar errores de forma graceful

- [ ] **2.2 Escribir tests para NovaRegistrationService**
  - Archivo: `tests/Feature/NovaRegistrationServiceTest.php`
  - Tests:
    - Registrar booking normalizado
    - Registrar order normalizado
    - Registrar transaction normalizado
    - Verificar source attribution
    - Verificar detección de duplicados

- [ ] **2.3 Integrar en NovaOrchestratorService**
  - Modificar: `app/Services/Nova/NovaOrchestratorService.php`
  - Añadir llamada a registro después de normalización
  - Verificar que el registro funciona en flujo real

**Criterio de éxito**: Tests pasan, registro funciona para bookings y orders.

---

## Fase 3: Filament Resources para Datos Externos (Prioridad MEDIA)

**Objetivo**: Crear recursos Filament para visualizar bookings/orders/transactions normalizados.

**Tiempo estimado**: 2 días

### Tareas

- [ ] **3.1 Crear NovaExternalBookingResource**
  - Archivo: `app/Filament/Resources/NovaExternalBookingResource.php`
  - Campos: source, external_id, intent_key, booking_date, booking_time, attendees, total, status
  - Filtros: source, status, date range
  - Acciones: view (read-only)

- [ ] **3.2 Crear NovaExternalOrderResource**
  - Archivo: `app/Filament/Resources/NovaExternalOrderResource.php`
  - Campos: source, external_id, status, payment_status, grand_total
  - Filtros: source, status, payment_status
  - Acciones: view (read-only)

- [ ] **3.3 Crear NovaExternalTransactionResource**
  - Archivo: `app/Filament/Resources/NovaExternalTransactionResource.php`
  - Campos: source, external_id, amount, currency, status
  - Filtros: source, status
  - Acciones: view (read-only)

- [ ] **3.4 Añadir a NovaBusinessResource**
  - Modificar: `app/Filament/Resources/NovaBusinessResource.php`
  - Añadir RelationManagers para bookings, orders, transactions
  - Permitir ver datos externos por negocio

**Criterio de éxito**: Resources funcionan en Filament, datos visibles correctamente.

---

## Fase 4: Ampliar Intenciones (Prioridad MEDIA)

**Objetivo**: Añadir nuevas intenciones a `nova_intent_rules` para cubrir más casos de uso.

**Tiempo estimado**: 1 día

### Tareas

- [ ] **4.1 Identificar intenciones faltantes**
  - Revisar casos de uso actuales
  - Identificar intenciones no cubiertas
  - Ejemplos: sales_purchase, route_recommendation, cancellation_request, physical_store_visit

- [ ] **4.2 Añadir reglas a nova_intent_rules**
  - Crear seeder para nuevas intenciones
  - Definir keywords para cada intención
  - Configurar rule_type (include/exclude/system_topic)

- [ ] **4.3 Actualizar mapeos en nova_intent_to_server_mapping**
  - Añadir mapeos para nuevas intenciones
  - Configurar priority y conditions si es necesario

**Criterio de éxito**: Nuevas intenciones detectadas correctamente, routing funciona.

---

## Fase 5: Tests End-to-End (Prioridad MEDIA)

**Objetivo**: Crear tests end-to-end para verificar el flujo completo.

**Tiempo estimado**: 2 días

### Tareas

- [ ] **5.1 Crear test de flujo completo booking**
  - Archivo: `tests/Feature/NovaBookingFlowTest.php`
  - Flujo: mensaje → detección → routing → MCP → normalización → registro → respuesta

- [ ] **5.2 Crear test de flujo completo order**
  - Archivo: `tests/Feature/NovaOrderFlowTest.php`
  - Flujo: mensaje → detección → routing → MCP → normalización → registro → respuesta

- [ ] **5.3 Crear test de routing dinámico**
  - Archivo: `tests/Feature/NovaDynamicRoutingTest.php`
  - Verificar que cambios en Filament afectan routing sin desplegar código

**Criterio de éxito**: Todos los tests end-to-end pasan.

---

## Fase 6: Documentación y Training (Prioridad BAJA)

**Objetivo**: Documentar el uso del sistema para administradores y desarrolladores.

**Tiempo estimado**: 1 día

### Tareas

- [ ] **6.1 Crear guía de administración Filament**
  - Cómo configurar mapeos intención→server
  - Cómo añadir nuevas intenciones
  - Cómo ver datos externos

- [ ] **6.2 Crear guía de integración de nuevos MCPs**
  - Pasos para integrar un nuevo MCP externo
  - Cómo añadir lógica de normalización
  - Ejemplos de integración

- [ ] **6.3 Actualizar documentación técnica**
  - Actualizar `docs/NOVA_ARCHITECTURE_MASTER.md` con estado final
  - Actualizar planes con estado completado

**Criterio de éxito**: Documentación completa y clara.

---

## Cronograma

| Fase | Duración | Start | End | Dependencias |
|------|----------|-------|-----|--------------|
| Fase 1: Normalización | 2-3 días | Día 1 | Día 3 | Ninguna |
| Fase 2: Registro | 2-3 días | Día 4 | Día 6 | Fase 1 |
| Fase 3: Filament Resources | 2 días | Día 7 | Día 8 | Fase 2 |
| Fase 4: Ampliar Intenciones | 1 día | Día 9 | Día 9 | Fase 1 |
| Fase 5: Tests E2E | 2 días | Día 10 | Día 11 | Fases 1-4 |
| Fase 6: Documentación | 1 día | Día 12 | Día 12 | Fases 1-5 |

**Total**: 12 días

---

## Riesgos y Mitigaciones

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|---------|------------|
| Normalización compleja para MCPs no estándar | Media | Alto | Crear framework de normalización extensible |
| Tests no cubren todos los casos edge | Media | Medio | Revisar tests con equipo QA |
| Cambios en APIs externos rompen normalización | Alta | Alto | Versionar lógica de normalización, monitorear errores |
| Filament resources no escalan con muchos datos | Baja | Medio | Implementar paginación y filtros eficientes |

---

## Métricas de Éxito

- **Fase 1**: 100% de tests de normalización pasan
- **Fase 2**: 100% de tests de registro pasan
- **Fase 3**: Filament resources cargan en <2 segundos con 10k registros
- **Fase 4**: 95% de detección de intención correcta en tests
- **Fase 5**: 100% de tests E2E pasan
- **Fase 6**: Documentación revisada y aprobada

---

## Próximos Pasos

1. Revisar este plan con el equipo
2. Ajustar prioridades y cronograma según feedback
3. Comenzar Fase 1: Normalización
4. Reunión diaria de 15 min para seguimiento
5. Demo al final de cada fase

---

## Estado

- [ ] Fase 1: Normalización de Respuestas
- [ ] Fase 2: Registro en Filament
- [ ] Fase 3: Filament Resources para Datos Externos
- [ ] Fase 4: Ampliar Intenciones
- [ ] Fase 5: Tests End-to-End
- [ ] Fase 6: Documentación y Training
