# Nova – Technical Specification

## Stack

- Laravel 12
- Livewire 4
- Flux UI
- Filament Panels 5
- Fortify
- Vite

## Paneles

### Portal
Interfaz para taxistas.

Características:

- mobile-first
- glass UI
- navegación por documentos
- dashboard simplificado

### App
Interfaz operativa para staff.

Incluye:

- gestión taxistas
- gestión documentos
- citas
- tickets
- dashboard interno

### Admin
Configuración del sistema.

Incluye:

- tipos
- categorías
- estados
- usuarios
- municipios
- configuraciones globales

---

## Arquitectura de dominios

### Taxi Domain

- Taxista
- Taxi
- Documentos
- Citas
- Tickets
- Gastos

### HRM Domain

- Employees
- Shifts
- TimeOff
- Attendance
- Departments

### Central Domain

- Departments
- Department schedules
- Department shift configuration


## Event System & Notifications
## Event System

Nova uses Laravel Events to decouple modules.

Events represent domain actions.

Examples:

TaxiBookingCreated
DocumentRequested
TicketCreated
EmployeeShiftAssigned

## Notification Channels

Different events may trigger different channels.

Channels used in Nova:

- WebSocket (real time UI updates)
- Email
- Database notifications
- Push notifications (future)
