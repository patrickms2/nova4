# Inventario Personal de Consulta - Patrick

## 📋 Resumen Rápido del Sistema

### Panels Disponibles
- **App Panel** (`/app`) - Panel de gestión principal
- **Portal Panel** (`/portal`) - Portal para taxistas

### Usuarios y Roles
- **Admin** - Acceso completo a todo
- **Soporte** - Gestión de tickets y ayuda
- **Departamento** - Acceso limitado a su departamento
- **Taxista** - Acceso solo a su portal

---

## 🏗️ Arquitectura del Sistema

### Estructura de Directorios Clave
```
/app
├── Filament/
│   ├── App/          # Panel de gestión
│   │   ├── Resources/     # Recursos (CRUDs)
│   │   ├── Pages/         # Páginas especiales
│   │   └── Widgets/       # Widgets del dashboard
│   └── Portal/       # Portal de taxistas
│       ├── Resources/
│       └── Pages/
├── Models/               # Modelos Eloquent
├── Observers/            # Observadores de eventos
├── Notifications/        # Notificaciones
└── Services/            # Lógica de negocio

/resources
├── views/
│   ├── components/       # Componentes Blade
│   └── livewire/        # Componentes Livewire

/database
├── migrations/          # Migraciones de BD
└── seeders/            # Datos de prueba

/tests
├── Feature/            # Tests de funcionalidad
└── Unit/              # Tests unitarios
```

---

## 🔧 Configuraciones Importantes

### 1. Servicios por Departamento
Los departamentos tienen flags booleanos para activar servicios:
- `has_meetings_service` - Citas
- `has_documents_service` - Documentos
- `has_tickets_service` - Tickets
- `has_chats_service` - Chat
- `has_shifts_service` - Turnos
- `has_attendance_service` - Asistencia
- `has_empleados_service` - Empledos
- `has_taxistas_service` - Taxistas
- `has_hoteles_service` - Hoteles
- `has_servicios_service` - Servicios Taxis
- `has_taxismapa_service` - Mapa Taxis

### 2. Helper Útil: `departmentHasService()`
```php
// En cualquier controlador/vista
$user->departmentHasService('tickets'); // true/false
```

### 3. Providers Importantes
- `AppServiceProvider` - Registro general
- `Filament/AppPanelProvider` - Config panel App
- `Filament/PortalPanelProvider` - Config panel Portal

---

## 📊 Recursos Principales (App Panel)

### Recursos de Taxista
- **TaxistaResource** - Gestión de taxistas
  - Tabla con filtros
  - Infolist con navegación rápida
  - Relaciones con taxis, documentos, citas, tickets

### Recursos de Empleados
- **EmployeeResource** - Gestión de empleados
  - Tabla con estado y departamento
  - Infolist con navegación rápida
  - Relaciones con turnos, asistencias

### Recursos de Soporte
- **TaxistaTicketResource** - Tickets de taxistas
  - Vista calendario de vencimientos
  - Priorización automática
  - Estados: abierto, en_progreso, resuelto, cerrado

### Recursos de Documentación
- **TaxistaDocumentResource** - Documentos de taxistas
  - Vista Kanban
  - Control de vencimientos
  - Tipos: licencia, seguro, ITV, etc.

---

## 📅 Páginas Especiales

### Gestión de Turnos
- **ShiftRoster** - Cuadrante de turnos
- **TimeOffRoster** - Control de ausencias
- **EmployeeMetrics** - Estadísticas

### Gestión de Asistencia
- **AttendanceRoster** - Registro de entradas/salidas
- **AttendanceResource** - Control de asistencias

### Mapas y Localización
- **Map**, **Map2**, **MapPage** - Vistas de mapa
- **Locations** - Gestión de ubicaciones

### Comunicación
- **DepartmentChats** - Chat entre departamentos

---

## 🔔 Sistema de Notificaciones

### Canales
- **Database** - Notificaciones persistentes
- **Reverb (WebSocket)** - Tiempo real
- **Email** - Comunicaciones importantes

### Notificaciones Clave
- `TicketCreatedNotification` - Nuevo ticket
- `PortalCredentialsNotification` - Credenciales portal
- `AppointmentApprovedNotification` - Cita aprobada

---

## 📱 Portal del Taxista

### Componentes del Portal
- **Dashboard** - Resumen de actividad
- **Mis Taxis** - Gestión de vehículos
- **Mis Citas** - Calendario de citas
- **Mis Documentos** - Archivo digital
- **Mis Gastos** - Control financiero
- **Chat** - Comunicación directa

### Ayuda del Portal
- Componente: `portal-help-guide.blade.php`
- Manual completo expandible
- Búsqueda en tiempo real
- Accesos rápidos

---

## 🎨 Componentes UI Reutilizables

### Componentes Blade
- `global-help-guide.blade.php` - Ayuda global
- `contextual-help.blade.php` - Ayuda contextual
- `taxista-nav-grid.blade.php` - Navegación rápida taxista
- `employee-nav-grid.blade.php` - Navegación rápida empleado

### Estilos
- **Tailwind CSS** - Framework de estilos
- **Alpine.js** - Interactividad frontend
- **Filament** - Framework de admin panels

---

## 🔍 Búsqueda y Navegación

### Command Palette (⌘K)
- Busca cualquier recurso
- Atajos frecuentes
- Acceso rápido a páginas

### Navegación por Grupos
- **Servicios de Taxista** - Todo relacionado con taxistas
- **Servicios de Empleados** - Gestión de personal
- **Departamentos** - Configuración departamental
- **Soporte** - Ayuda y tickets
- **Administración** - Configuración general (solo admin)

---

## 📝 Tests Importantes

### Tests de Funcionalidad
- `SupportTicketTest.php` - Tests completos del sistema
  - Helper departmentHasService
  - Navegación condicional
  - Componentes de ayuda
  - 34 tests, 137 assertions

### Ejecutar Tests
```bash
# Todos los tests
php artisan test

# Tests específicos
php artisan test tests/Feature/SupportTicketTest.php

# Tests con filtro
php artisan test --filter="global help"
```

---

## 🛠️ Comandos Útiles

### Artisan
```bash
# Listar todos los comandos
php artisan list

# Crear nuevo recurso
php artisan make:filament-resource MiResource

# Limpiar caché
php artisan optimize:clear

# Ver rutas
php artisan route:list
```

### Desarrollo
```bash
# Iniciar servidor local
php artisan serve

# Ejecutar migraciones
php artisan migrate

# Crear seeder
php artisan make:seeder MiSeeder
```

---

## 🔐 Seguridad y Permisos

### Gates y Policies
- Los recursos usan `can()` para verificar permisos
- Admin tiene acceso a todo
- Los departamentos solo ven su contenido

### Middleware
- Autenticación requerida en todos los paneles
- Verificación de departamento para servicios activos

---

## 📈 Estadísticas y Métricas

### Dashboards
- **App Dashboard** - Vista general para admin
- **Portal Dashboard** - Resumen para taxista
- **Employee Metrics** - Estadísticas de empleados

### Reportes
- Exportación a Excel/PDF
- Filtros por fecha y departamento
- Gráficos interactivos

---

## 🚀 Despliegue y Producción

### Configuración
- Variables de entorno en `.env`
- Configuración de caché
- Optimización de assets

### Comandos de Producción
```bash
# Optimizar para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Compilar assets
npm run build
```

---

## 📞 Contactos y Soporte

### Enlaces Rápidos
- **Documentación**: `/docs/`
- **Inventario de Recursos**: `/docs/app-resource-inventory.md`
- **Arquitectura**: `/docs/event-notification-architecture.md`

### Recordatorios
- Mantener tests actualizados
- Documentar nuevos componentes
- Revisar permisos al añadir recursos
- Crear capturas para manuales de ayuda

---

## 🔄 Actualizaciones Pendientes

### Mejoras en Progreso
- [ ] Completar capturas de pantalla para manuales
- [ ] Implementar Spotlight commands para Support

### Ideas Futuras
- [ ] Sistema de notificaciones push:completo
- [ ] Integración con GPS en tiempo real
- [ ] App móvil nativephp
- [ ] Sistema de reportes avanzados

---

## 🚀 Configuración Rápida (Para Mañana)

### Comandos de Creación
```bash
# Usuario Admin
php artisan tinker
$user = \App\Models\User::create([
    'name' => 'Administrador',
    'email' => 'admin@nova.test',
    'password' => Hash::make('password123'),
    'role' => 'admin',
    'status' => true,
]);

# Departamento con todos los servicios
$dept = \App\Models\BookingDepartment::create([
    'name' => 'Operaciones',
    'has_meetings_service' => true,
    'has_documents_service' => true,
    'has_tickets_service' => true,
    'has_chats_service' => true,
    'has_shifts_service' => true,
    'has_attendance_service' => true,
]);

# Taxista de prueba
$taxista = \App\Models\Taxista::create([
    'name' => 'María García',
    'email' => 'maria@nova.test',
    'password' => Hash::make('taxista123'),
    'phone' => '600123456',
    'license_number' => 'TAX-001',
]);
```

### Verificación
```bash
php artisan test --compact  # Debe pasar 36 tests
php artisan optimize:clear   # Limpiar caché
npm run build               # Build frontend
```

---

*Última actualización: 8 de Marzo de 2026 - Añadida configuración rápida*
*Patrick M. S.*
