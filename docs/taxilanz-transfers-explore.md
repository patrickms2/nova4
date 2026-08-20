---
title: Taxilanz Transfers Explore Integration
---

# Taxilanz Transfers en Explore

## Flujo público

1. `/explore` muestra un pseudo-servicio `transfer` basado en una ruta Woo activa.
2. El formulario pide origen, destino, fecha/hora, pasajeros y datos del cliente.
3. Antes de crear la solicitud, Explore llama a `/explore/transfer-estimate`.
4. Ese endpoint ejecuta el tool MCP `transfer_price_estimate` del server `taxilanz-transfers`.
5. El precio calculado se envía como `base_price` en `/explore/requests`.
6. La solicitud se guarda en `public_booking_requests` con:
   - `type = transfer`
   - `booking_kind = transfer`
   - `pickup_address`
   - `dropoff_address`
   - `passengers`
   - `base_price` igual a la tarifa transfer
7. Redsys cobra `payment_amount_cents` calculado desde `base_price`.
8. Después del pago, el flujo remoto Woo/CHBS recibe pickup, dropoff, fecha, hora, pasajeros y referencia Nova.

## Zonas tarifarias

Las tarifas no dependen del nombre libre del hotel. El flujo correcto es:

```text
hotel/location → tariff_zone → transfer_tariffs → price
```

Las columnas usadas son:

- `hotels.tariff_zone`
- `locations.tariff_zone`
- `transfer_tariffs.origin_zone`
- `transfer_tariffs.destination_zone`

## Comandos

Asignar zonas a hoteles y ubicaciones:

```bash
php artisan nova:assign-taxilanz-tariff-zones
```

Sembrar tarifas editables:

```bash
php artisan nova:seed-transfer-tariffs
```

Recrear MCP Taxilanz Transfers:

```bash
php artisan nova:seed-taxilanz-transfers-mcp
```

## Administración Filament

Las tarifas se editan en Filament:

```text
Nova → Tarifas transfers
```

Cada tarifa tiene:

- Zona origen
- Zona destino
- Precio
- Moneda
- Recargo festivo
- IGIC
- IGIC incluido
- Activa

## Reservas transfer

Las reservas de transfer se localizan en `public_booking_requests` filtrando:

```text
type = transfer
```

El backend sigue usando el servicio `Tour` asociado a Woo internamente para reutilizar Redsys y CHBS, pero el registro local queda como tipo real `transfer`.

## AI-bot y venta cruzada

El AI-bot detecta traslados como `taxi_booking` y, al confirmar, crea una reserva real usando:

```text
app/Actions/Taxi/CreateTransferBookingRequestFromNovaConversation.php
```

La acción:

1. Lee origen, destino, fecha, hora, pasajeros y contacto desde la conversación.
2. Ejecuta `transfer_price_estimate`.
3. Crea `PublicBookingRequest` con `type = transfer`.
4. Crea el booking remoto Woo/CHBS.
5. Prepara Redsys con `payment_amount_cents`.
6. Devuelve al bot un enlace `public.redsys.start`.

Este flujo permite ofrecer transfers como venta cruzada después de visitas o restaurantes manteniendo el mismo backend que Explore.

## Paquete local y pago único

La Fase 1 de paquetes permite crear una solicitud local `type = package` con varias líneas en:

```text
public_booking_request_items
```

Ejemplo:

```text
Visita La Geria                         2 × 15€ = 30€
Transfer Puerto del Carmen → La Geria   1 × 21€ = 21€
Subtotal                                         51€
Descuento paquete 10%                         -5,10€
Total Redsys                                  45,90€
```

El paquete se crea con:

```text
app/Actions/Booking/CreatePackageBookingRequest.php
```

Cuando Redsys marca el paquete como `paid`, se ejecuta:

```text
app/Actions/Booking/FulfillPackageBookingRequest.php
```

La acción crea reservas hijas locales para los items y ejecuta la creación remota disponible.

Estados de item:

```text
created         Reserva remota creada.
pending_manual  Requiere intervención manual.
failed          Falló la creación remota.
```

Para LatePoint, si el cliente no aporta email real, el item de visita queda `pending_manual` porque LatePoint exige email de cliente. El transfer sí puede crearse automáticamente solo con nombre y teléfono.
