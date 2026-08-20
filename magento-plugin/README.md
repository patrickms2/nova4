# Lanzaloe Magento Plugin — NovaGestion Bundle Orders

Este módulo expone un endpoint REST propio para crear pedidos en Lanzaloe desde NovaGestion, evitando el checkout de invitado y sus términos y condiciones.

## Endpoint

```
POST /rest/all/V1/novagestion/create-order
Authorization: Bearer <admin_token>
```

## Payload

```json
{
  "sku": "jugo_puro_250",
  "qty": 1,
  "customer": {
    "email": "cliente@example.com",
    "firstname": "Nombre",
    "lastname": "Apellido",
    "telephone": "600000000",
    "street": ["Calle Falsa 123"],
    "city": "Arrecife",
    "postcode": "35500",
    "country_id": "ES",
    "region_code": "Las Palmas",
    "company": "Empresa S.L."
  },
  "shippingMethod": "amstrates7",
  "shippingCarrier": "amstrates",
  "paymentMethod": "banktransfer"
}
```

## Instalación en Lanzaloe

1. Copiar la carpeta `Novagestion/OrderApi` a:
   ```
   app/code/Novagestion/OrderApi
   ```

2. Habilitar el módulo:
   ```bash
   php bin/magento module:enable Novagestion_OrderApi
   php bin/magento setup:upgrade
   php bin/magento cache:flush
   php bin/magento setup:di:compile
   ```

3. Generar o usar un token de admin REST:
   ```bash
   php bin/magento admin:token
   ```

4. Probar el endpoint con cURL:
   ```bash
   curl -X POST https://www.lanzaloe.com/rest/all/V1/novagestion/create-order \
     -H "Authorization: Bearer <TOKEN>" \
     -H "Content-Type: application/json" \
     -d '{ ... }'
   ```

## Notas

- El módulo crea el cliente si no existe.
- Crea un carrito para ese cliente y lo convierte en pedido directamente.
- Usa `placeOrder` de Magento, por lo que stock, impuestos y reglas de envío se aplican normalmente.
- El endpoint requiere permisos de ventas (`Magento_Sales::sales`).
