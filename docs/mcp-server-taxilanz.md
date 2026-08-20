# Taxilanz MCP Server Documentation

## Overview

El MCP server de Taxilanz expone las funcionalidades del sistema de gestión de taxis con 180+ hoteles conectados a través del protocolo Model Context Protocol (MCP).

## Server Information

- **Name**: Taxilanz MCP Server
- **Version**: 1.0.0
- **Base URL**: `https://tu-dominio.com/api/mcp`
- **Description**: MCP server for Taxilanz taxi management system with 180+ hotels

## API Endpoints

### Server Info
```http
GET /api/mcp/info
```

### List Tools
```http
GET /api/mcp/tools
```

### Execute Tool
```http
POST /api/mcp/execute
Content-Type: application/json

{
  "name": "tool_name",
  "arguments": { ... }
}
```

## Available Tools

### Gestión de Hoteles

#### `hotel_list`
List all connected hotels with status and activity.

**Input:**
```json
{
  "status": "active",      // "active", "inactive", "all"
  "zone": "all",          // "tias", "yaiza", "arrecife", "playa_blanca", "all"
  "page": 1,
  "per_page": 50
}
```

**Output:**
```json
{
  "success": true,
  "data": {
    "hotels": [
      {
        "id": 1,
        "name": "FARO PARK ISLAND",
        "zone": 1,
        "status": "active",
        "location": {
          "lat": 28.9638,
          "lng": -13.5485,
          "address": "Calle Principal, Lanzarote"
        },
        "phone": "+34 928 XXX XXX",
        "services_today": 5,
        "services_month": 150,
        "reservations_today": 3,
        "reservations_month": 90
      }
    ],
    "summary": {
      "total_active": 57,
      "total_inactive": 0,
      "updated_at": "2026-05-21 05:00:00"
    }
  },
  "meta": {
    "total": 57,
    "page": 1,
    "per_page": 50,
    "has_more": false
  }
}
```

#### `hotel_get`
Get specific hotel details and status.

**Input:**
```json
{
  "id": 1
}
```

#### `hotel_status_update`
Update hotel connection status.

**Input:**
```json
{
  "id": 1,
  "status": "active"  // "active" or "inactive"
}
```

#### `hotel_stats_get`
Get hotel statistics (services, reservations).

**Input:**
```json
{
  "hotel_id": 1,
  "period": "today"  // "today", "week", "month", "year"
}
```

### Estadísticas por Zona

#### `zone_stats_get`
Get taxi statistics by zone (Tias, Yaiza, etc.).

**Input:**
```json
{
  "zone": "all",      // "tias", "yaiza", "arrecife", "playa_blanca", "all"
  "period": "today"   // "today", "month"
}
```

**Output:**
```json
{
  "success": true,
  "data": {
    "tias": {"today": 15, "month": 450},
    "yaiza": {"today": 12, "month": 360},
    "arrecife": {"today": 8, "month": 240},
    "playa_blanca": {"today": 5, "month": 150},
    "others": {"today": 3, "month": 90}
  }
}
```

#### `zone_total_get`
Get total taxi requests by zone.

**Input:**
```json
{
  "period": "today"  // "today", "month"
}
```

### Reservas de Taxi

#### `booking_create`
Create taxi booking with real-time Auriga API integration.

**Input:**
```json
{
  "customer_phone": "+34 646 426 442",
  "customer_name": "Patrick",
  "pickup_location": "Faro Park Island, Lanzarote",
  "dropoff_location": "Aeropuerto Lanzarote",
  "pickup_hotel_id": 1,
  "date": "2026-05-22",
  "time": "08:00",
  "passengers": 2,
  "payment_method": "card",  // "cash", "card", "revolut", "bizum"
  "use_reward_points": false,
  "receptionist_id": 123
}
```

**Output:**
```json
{
  "success": true,
  "data": {
    "booking_id": 12345,
    "auriga_booking_id": "AUR-1716273600",
    "status": "pending",
    "estimated_price": 15.50,
    "eta": "15 min"
  }
}
```

#### `booking_get`
Get specific taxi booking.

**Input:**
```json
{
  "id": 12345
}
```

#### `booking_list`
List taxi bookings with filters.

**Input:**
```json
{
  "hotel_id": 1,
  "date": "2026-05-21",
  "status": "pending",
  "customer_phone": "+34 646 426 442",
  "zone": "tias",
  "page": 1,
  "per_page": 20
}
```

#### `booking_cancel`
Cancel taxi booking.

**Input:**
```json
{
  "id": 12345
}
```

### Servicios Recientes

#### `service_list_latest`
Get latest taxi services.

**Input:**
```json
{
  "limit": 10,
  "zone": "tias"
}
```

**Output:**
```json
{
  "success": true,
  "data": [
    {
      "id": 12345,
      "hotel_name": "FARO PARK ISLAND",
      "date": "2026-05-21 08:30",
      "status": "completed",
      "driver": "Juan García"
    }
  ]
}
```

### Conductores

#### `driver_get_available`
Get available drivers from Auriga API.

**Input:**
```json
{s
  "location": "Faro Park Island, Lanzarote",
  "date": "2026-05-22",
  "time": "08:00"
}
```

**Output:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Juan García",
      "phone": "+34 612 XXX XXX",
      "license": "TAX-12345",
      "location": {
        "lat": 28.9638,
        "lng": -13.5485
      }
    }
  ]
}
```

#### `driver_list`
List all drivers with status.

**Input:**
```json
{
  "status": "available",  // "available", "busy", "offline", "all"
  "zone": "tias",
  "page": 1,
  "per_page": 20
}
```

### Mapa y Ubicaciones

#### `location_map_markers`
Get map markers for hotels and active services.

**Input:**
```json
{
  "zone": "tias",
  "show_hotels": true,
  "show_active_services": true
}
```

**Output:**
```json
{
  "success": true,
  "data": {
    "markers": [
      {
        "type": "hotel",
        "id": 1,
        "name": "FARO PARK ISLAND",
        "location": {"lat": 28.9638, "lng": -13.5485},
        "status": "active"
      },
      {
        "type": "active_service",
        "id": 12345,
        "location": {"lat": 28.9640, "lng": -13.5490},
        "status": "in_progress"
      }
    ],
    "count": 2
  }
}
```

### Estimaciones

#### `price_estimate`
Get price estimate for route.

**Input:**
```json
{
  "pickup_location": "Faro Park Island, Lanzarote",
  "dropoff_location": "Aeropuerto Lanzarote",
  "distance_km": 15
}
```

**Output:**
```json
{
  "success": true,
  "data": {
    "pickup_location": "Faro Park Island, Lanzarote",
    "dropoff_location": "Aeropuerto Lanzarote",
    "distance_km": 15,
    "estimated_price": 21.50,
    "currency": "EUR"
  }
}
```

## Configuration

### Environment Variables

Agregar al archivo `.env`:

```env
# Auriga API Configuration
AURIGA_ENDPOINT=https://api.auriga.example.com
AURIGA_API_KEY=your_auriga_api_key_here
```

## Usage Examples

### Example 1: List Active Hotels

```bash
curl -X GET "https://tu-dominio.com/api/mcp/tools" \
  -H "Accept: application/json"
```

```bash
curl -X POST "https://tu-dominio.com/api/mcp/execute" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "hotel_list",
    "arguments": {
      "status": "active",
      "per_page": 20
    }
  }'
```

### Example 2: Create Taxi Booking

```bash
curl -X POST "https://tu-dominio.com/api/mcp/execute" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "booking_create",
    "arguments": {
      "customer_phone": "+34 646 426 442",
      "customer_name": "Patrick",
      "pickup_location": "Faro Park Island, Lanzarote",
      "dropoff_location": "Aeropuerto Lanzarote",
      "date": "2026-05-22",
      "time": "08:00",
      "passengers": 2,
      "payment_method": "card"
    }
  }'
```

### Example 3: Get Zone Statistics

```bash
curl -X POST "https://tu-dominio.com/api/mcp/execute" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "zone_stats_get",
    "arguments": {
      "zone": "all",
      "period": "today"
    }
  }'
```

## Integration with Chatbot

### WhatsApp Chatbot Integration

```javascript
// Cliente AI para WhatsApp
async function handleTaxiRequest(message, phone) {
  // Detectar intent de taxi
  const intent = await detectTaxiIntent(message);
  
  if (intent.type === 'book_taxi') {
    // Crear reserva vía MCP
    const booking = await callMCPTool('booking_create', {
      customer_phone: phone,
      customer_name: intent.name,
      pickup_location: intent.pickup,
      dropoff_location: intent.dropoff,
      date: intent.date,
      time: intent.time,
      passengers: intent.passengers
    });
    
    return `✅ Taxi reservado
📍 Recogida: ${intent.pickup}
🏁 Destino: ${intent.dropoff}
⏰ ${intent.date} a las ${intent.time}
💰 Precio estimado: €${booking.data.estimated_price}`;
  }
}
```

### Receptionist Dashboard Integration

```javascript
// Dashboard para recepcionistas
async function loadHotelStats(hotelId) {
  const stats = await callMCPTool('hotel_stats_get', {
    hotel_id: hotelId,
    period: 'today'
  });
  
  return stats.data;
}

async function createBookingForGuest(hotelId, guestData) {
  const booking = await callMCPTool('booking_create', {
    pickup_hotel_id: hotelId,
    customer_phone: guestData.phone,
    customer_name: guestData.name,
    pickup_location: guestData.hotelAddress,
    dropoff_location: guestData.destination,
    date: guestData.date,
    time: guestData.time,
    receptionist_id: guestData.receptionistId
  });
  
  return booking;
}
```

## Error Handling

### Standard Error Response

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human readable error message"
  }
}
```

### Common Error Codes

- `TOOL_NOT_FOUND`: The requested tool does not exist
- `EXECUTION_ERROR`: Error during tool execution
- `VALIDATION_ERROR`: Invalid input parameters
- `NOT_FOUND`: Requested resource not found
- `UNAUTHORIZED`: Authentication required

## Rate Limiting

- **Default**: 100 requests per minute per IP
- **Burst**: 10 requests per second

## Authentication

Currently the MCP server does not require authentication. For production use, implement:

1. API Key authentication
2. JWT tokens for authenticated users
3. Rate limiting per user

## Support

For issues or questions:
- Email: support@taxilanz.com
- Documentation: https://docs.taxilanz.com/mcp
- GitHub Issues: https://github.com/taxilanz/mcp-server

## Version History

### v1.0.0 (2026-05-21)
- Initial release
- Hotel management tools
- Zone statistics
- Booking management
- Driver management
- Map markers
- Price estimation
