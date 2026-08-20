# Migrar Canales a Recursos Nova

> **Objetivo**: Eliminar lógica hardcoded en canales (ai-bot, chat, WhatsApp) y usar recursos Nova configurados en Filament

---

## Estado Actual

**NovaOrchestratorService** (`app/Services/Nova/NovaOrchestratorService.php`):
- ✅ Ya usa `NovaIntentToServerMapping` para routing dinámico (líneas 188-234)
- ✅ Ya usa `NovaKnowledgeService` para knowledge
- ✅ Ya usa `NovaCrossSellingService` para cross-selling
- ✅ Ya usa `Prompt` model para prompts
- ❌ **Hardcoded**: Servers específicos (sirvo, lageria, taxilanz) en `runLocalTourismScenario` (líneas 52-58)
- ❌ **Hardcoded**: Config fallback con types hardcoded (líneas 158-162)
- ❌ **Hardcoded**: Fallback legacy con match (líneas 212-218)

## Problemas Identificados

### 1. Hardcoded Server Calls (Líneas 52-58)
```php
$sirvo = $this->activeServer('sirvo');
$lageria = $this->activeServer('la_geria');
$taxilanz = $this->activeServer('taxi_lanzaloe');
```

**Problema**: Los servers están hardcoded en el código en lugar de ser dinámicos.

**Solución**: Usar `NovaIntentToServerMapping` para determinar qué servers se necesitan según el intent detectado.

### 2. Hardcoded Config Fallback (Líneas 158-162)
```php
'la_geria' => config('services.nova.lageria_endpoint_url'),
'taxilanz' => config('services.nova.taxilanz_endpoint_url'),
```

**Problema**: Los config keys están hardcoded con nombres específicos.

**Solución**: Usar la tabla `Server` como única fuente de verdad para endpoints.

### 3. Hardcoded Legacy Fallback (Líneas 212-218)
```php
$server = match ($intentKey) {
    'restaurant_booking' => $this->activeServer('sirvo'),
    'winery_visit' => $this->activeServer('la_geria'),
    'taxi_booking' => $this->activeServer('taxi_lanzaloe'),
    'product_purchase' => $this->activeServer('lanzaloe'),
    default => null,
};
```

**Problema**: Fallback legacy con hardcoded mapping.

**Solución**: Eliminar este fallback y confiar 100% en `NovaIntentToServerMapping`.

---

## Plan de Refactorización

### Paso 1: Detectar Intent con NovaIntentRule
**Estado**: Ya implementado en `NovaConversationDataExtractor`

### Paso 2: Obtener Server/Tool desde NovaIntentToServerMapping
**Estado**: Ya implementado en `getServerForIntent()` (líneas 188-234)

### Paso 3: Eliminar Hardcoded Server Calls
**Cambios en `runLocalTourismScenario`**:

**ANTES (Líneas 52-58):**
```php
$sirvo = $this->activeServer('sirvo');
$lageria = $this->activeServer('la_geria');
$taxilanz = $this->activeServer('taxi_lanzaloe');

$sirvoConfig = $sirvo ? $this->probeSirvo($sirvo) : null;
$lageriaMcp = $lageria ? $this->probeLaGeria($lageria) : null;
$taxilanzMcp = $taxilanz ? $this->probeTaxilanz($taxilanz) : null;
```

**DESPUÉS:**
```php
// Detectar intent primero
$conversation = $this->dataExtractor->extract(...);

// Obtener server dinámicamente según intent
$serverMapping = $this->getServerForIntent($conversation['intent'], $business);
$server = $serverMapping['server'];

// Probar server si existe
$serverConfig = $server ? $this->probeServer($server) : null;
```

### Paso 4: Eliminar Hardcoded Config Fallback
**Cambios en `activeServer()` (líneas 145-176):**

**ANTES:**
```php
$endpointUrl = match ($type) {
    'sirvo' => config('services.nova.sirvo_endpoint_url'),
    'la_geria' => config('services.nova.lageria_endpoint_url'),
    'lanzaloe' => config('services.nova.lanzaloe_endpoint_url'),
    'taxilanz' => config('services.nova.taxilanz_endpoint_url'),
    'taxilanz_hoteles' => config('services.nova.taxilanz_hoteles_endpoint_url'),
    default => null,
};
```

**DESPUÉS:**
```php
// Eliminar config fallback - usar solo tabla Server
// Si no está en DB, no existe (configurar en Filament)
return null;
```

### Paso 5: Eliminar Legacy Fallback
**Cambios en `getServerForIntent()` (líneas 210-224):**

**ANTES:**
```php
if (!$mapping) {
    // Fallback to legacy method for backward compatibility
    $server = match ($intentKey) {
        'restaurant_booking' => $this->activeServer('sirvo'),
        'winery_visit' => $this->activeServer('la_geria'),
        'taxi_booking' => $this->activeServer('taxi_lanzaloe'),
        'product_purchase' => $this->activeServer('lanzaloe'),
        default => null,
    };
    return ['server' => $server, 'tool' => null, 'mapping' => null];
}
```

**DESPUÉS:**
```php
if (!$mapping) {
    // Sin mapping = sin configuración
    return ['server' => null, 'tool' => null, 'mapping' => null];
}
```

### Paso 6: Crear Método Genérico probeServer()
**Nuevo método para reemplazar probeSirvo, probeLaGeria, probeTaxilanz:**

```php
private function probeServer(Server $server): array
{
    return match ($server->type) {
        'sirvo' => $this->probeSirvo($server),
        'la_geria' => $this->probeLaGeria($server),
        'taxi_lanzaloe' => $this->probeTaxilanz($server),
        default => $this->probeGenericServer($server),
    };
}
```

---

## Configuración Requerida en Filament

Para que esto funcione, se requiere configurar en Filament:

### 1. Nova Intent Rules (Ya existen 15)
- `taxi_booking`
- `restaurant_booking`
- `winery_visit`
- `product_purchase`
- `product_info`
- `route_recommendation`
- `commercial_info`
- etc.

### 2. Nova Intent to Server Mapping (Nuevos mapeos requeridos)
- Crear mapeos para cada intent → server + tool
- Ver guía: `docs/guides/pasos-finales-configuracion.md`

### 3. Servers (Ya existen en DB)
- Sirvo Restaurants MCP
- La Geria Shop+Tours MCP
- Taxilanz Transfers MCP
- Lanzaloe Magento MCP
- etc.

---

## Beneficios de la Migración

1. **Sin hardcoded**: Toda la configuración en Filament
2. **Fácil agregar nuevos servers**: Solo agregar en Filament, sin tocar código
3. **Fácil cambiar routing**: Editar mapeos en Filament
4. **Consistencia**: Todos los canales usan la misma configuración
5. **Escalabilidad**: Agregar nuevos intents/servers sin cambios de código

---

## Pruebas Después de Migración

1. **Test ai-bot**: `/ai-bot` debe funcionar con configuración Nova
2. **Test chat**: `/chat` debe funcionar con configuración Nova
3. **Test WhatsApp**: Webhook debe funcionar con configuración Nova
4. **Test nuevo server**: Agregar server en Filament y probar routing
