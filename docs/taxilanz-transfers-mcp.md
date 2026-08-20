# Taxilanz Transfers MCP

## Objetivo

`Taxilanz Transfers MCP` es el MCP dinámico de Nova para gestionar traslados entre hoteles y locations locales de Taxilanz.

Su función es separar la lógica de transfers de:

- `Taxilanz Hoteles Laravel MCP`: hoteles, recepcionistas, servicios históricos y comisiones.
- `Taxilanz Chauffeur Booking`: CHBS, rutas chauffeur, reservas WordPress y pedidos WooCommerce.

Este MCP trabaja sobre datos locales ya importados en Nova, principalmente:

- `hotels`
- `locations`
- `external_catalog_items`
- `external_sync_mappings`

## Registro

El MCP se registra por código mediante el comando:

```bash
php artisan nova:seed-taxilanz-transfers-mcp
```

Archivo:

```text
app/Console/Commands/NovaSeedTaxilanzTransfersMcp.php
```

El comando es idempotente. Puede ejecutarse varias veces sin duplicar servidor ni tools.

## Server

```text
name: Taxilanz Transfers MCP
slug: taxilanz-transfers
endpoint: /mcp/taxilanz-transfers
transport: web
version: 1.0.0
```

## Tools

### `transfer_locations`

Busca hoteles y locations locales utilizables como origen o destino de un traslado.

Input:

```json
{
  "query": "Princesa",
  "limit": 5
}
```

Output ejemplo:

```json
{
  "query": "Princesa",
  "count": 1,
  "results": [
    {
      "type": "hotel",
      "id": 5,
      "name": "PRINCESA YAIZA",
      "location_id": 36,
      "latitude": null,
      "longitude": null,
      "address": "Av. de Papagayo, 22, 35580 Playa Blanca, Las Palmas, España, Lanzarote, Spain"
    }
  ]
}
```

Uso previsto:

- Autocomplete de origen.
- Autocomplete de destino.
- Normalización de nombres de hotel/location antes de crear reserva.

### `transfer_price_estimate`

Resuelve origen y destino por nombre y calcula un precio estimado si hay coordenadas.

Input:

```json
{
  "pickup_location": "Hotel Princesa Yaiza",
  "dropoff_location": "Hotel Beatriz Costa Teguise",
  "passengers": 2
}
```

Output actual probado:

```json
{
  "ok": true,
  "pickup": {
    "type": "hotel",
    "id": 5,
    "name": "PRINCESA YAIZA",
    "location_id": 36,
    "latitude": null,
    "longitude": null
  },
  "dropoff": {
    "type": "hotel",
    "id": 6,
    "name": "BEATRIZ COSTA",
    "location_id": 37,
    "latitude": null,
    "longitude": null
  },
  "passengers": 2,
  "distance_km": null,
  "currency": "EUR",
  "estimated_price": null,
  "pricing_basis": "missing_coordinates"
}
```

Notas:

- La tool ya resuelve nombres parciales y nombres con prefijo `Hotel`.
- Actualmente no puede calcular precio cuando las locations no tienen coordenadas.
- Si existen coordenadas, usa una estimación local basada en distancia Haversine:
  - base: `12.00 EUR`
  - por km: `1.45 EUR`
  - suplemento desde 5 pasajeros: `6.00 EUR` por pasajero adicional

### `transfer_booking_payload`

Prepara un payload normalizado para pasar al flujo de pago/reserva.

Input:

```json
{
  "customer_name": "Patrick Test",
  "customer_phone": "+34646426442",
  "pickup_location": "Hotel Princesa Yaiza",
  "dropoff_location": "Hotel Beatriz Costa Teguise",
  "pickup_date": "2026-06-08",
  "pickup_time": "13:00",
  "passengers": 2,
  "amount": 140
}
```

Output ejemplo:

```json
{
  "type": "transfer",
  "customer_name": "Patrick Test",
  "customer_email": "",
  "customer_phone": "+34646426442",
  "origin": "Hotel Princesa Yaiza",
  "destination": "Hotel Beatriz Costa Teguise",
  "pickup_date": "2026-06-08",
  "pickup_time": "13:00",
  "passengers": 2,
  "amount": 140,
  "currency": "EUR",
  "source": "taxilanz-transfers-mcp",
  "ready_for_payment": true
}
```

Uso previsto:

- Construir una `PublicBookingRequest` en Nova.
- Enviar al usuario a pago Redsys Nova.
- Tras pago correcto, crear `ExternalBooking`, `ExternalPayment` y exportar a CHBS/Woo si corresponde.

## Importación de hoteles

Los hoteles de Taxilanz se importan desde la fuente externa:

```text
Taxilanz Hoteles · Mcp · Hoteles
source_platform: mcp
resource_type: hotel
target_model: hotel
```

El importador está en:

```text
app/Services/ExternalSync/ExternalSourceSynchronizer.php
```

Para fuentes `mcp` de hoteles, llama a:

```text
/api/mcp/tools/get_hotels
```

y proyecta cada registro como:

- `ExternalCatalogItem`
- `Hotel`
- `Location`
- `ExternalSyncMapping`

Verificación realizada:

```text
processed: 10
hotels: 12
locations: 29
```

Hoteles confirmados:

```text
PRINCESA YAIZA
BEATRIZ COSTA
```

## Flujo previsto completo

```text
1. Buscar origen/destino
   transfer_locations

2. Estimar precio
   transfer_price_estimate

3. Preparar payload
   transfer_booking_payload

4. Crear PublicBookingRequest en Nova

5. Cobrar con Redsys Nova

6. Tras pago correcto:
   - marcar Booking local como pagado
   - crear/actualizar ExternalBooking
   - crear/actualizar ExternalPayment
   - exportar a CHBS/Woo si aplica
```

## Relación con CHBS y WooCommerce

Este MCP no crea directamente reservas CHBS ni pedidos WooCommerce.

La creación real en Taxilanz WordPress debe pasar por:

```text
POST /wp-json/taxilanz-mcp/v1/chauffeur/bookings
```

Ese endpoint crea:

- `chbs_booking`
- pedido WooCommerce
- pago Woo marcado como `redsys` / `completed`

## Pendientes

### Coordenadas

Para que `transfer_price_estimate` calcule precio automático, las `locations` deben tener:

```text
latitude
longitude
```

Actualmente algunos hoteles importados tienen dirección pero no coordenadas.

### Tarifas reales

El precio estimado por Haversine es solo fallback.

Opciones preferidas:

1. Usar tarifas fijas CHBS si existe ruta compatible.
2. Crear tabla local de zonas/precios.
3. Geocodificar hoteles y calcular distancia real.

### Tool de creación final

Falta una tool definitiva para crear reserva pagada o iniciar flujo, por ejemplo:

```text
transfer_booking_create
```

Esa tool debería crear una `PublicBookingRequest` y devolver enlace de pago Redsys Nova, no crear CHBS directamente antes de cobrar.

## Comandos útiles

Seed del MCP:

```bash
php artisan nova:seed-taxilanz-transfers-mcp
```

Sync de hoteles Taxilanz desde Tinker:

```php
$source = App\Models\ExternalSource::findOrFail(31);
app(App\Services\ExternalSync\ExternalSourceSynchronizer::class)->sync($source, true);
```

Probar tool desde Tinker:

```php
$action = app(App\Actions\Workflow\CallMcpToolAction::class);

$action([
    'server_slug' => 'taxilanz-transfers',
    'tool_name' => 'transfer_price_estimate',
    'input' => [
        'pickup_location' => 'Hotel Princesa Yaiza',
        'dropoff_location' => 'Hotel Beatriz Costa Teguise',
        'passengers' => 2,
    ],
]);
```
