<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use App\Support\SupportAccess;

class PortalDocumentation extends Page
{

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Documentación Portal';
    protected static ?int $navigationSort = 101;
    protected string $view = 'filament.app.pages.portal-documentation';

    public static function shouldRegisterNavigation(): bool
    {
        return SupportAccess::canAccess(auth()->user());
    }

    public static function canAccess(): bool
    {
        return SupportAccess::canAccess(auth()->user());
    }

    public function getTitle(): string
    {
        return 'Documentación Portal Taxista';
    }

    public function getSubheading(): string
    {
        return 'Guías completas de comandos y sistemas';
    }

    public function downloadDocumentation()
    {
        // Generate PDF or markdown file with documentation
        $content = $this->generateDocumentationFile();

        // This would generate a downloadable file
        return response()->streamDownload(
            function () use ($content) {
                echo $content;
            },
            'portal-documentation.md'
        );
    }

    private function generateDocumentationFile(): string
    {
        return <<<DOC
# TAXILANZ PORTAL - Documentación Completa

## 🚀 SISTEMA DE BACKUP INTERACTIVO

### Comandos de Backup
```bash
# Backup interactivo con menú visual
./scripts/portal-master.sh backup interactive

# Backup rápido del Portal
./scripts/portal-master.sh backup quick

# Backup completo
./scripts/portal-master.sh backup full

# Backup asíncrono
php artisan portal:async backup --categories=portal,models,config
```

### Categorías de Backup
- 🚪 Portal Taxista: Componentes Livewire, vistas y CSS
- ⚡ Componentes Livewire: Todos los componentes Livewire
- 🎛️ Panel Filament: Resources, Pages y Components
- 📦 Modelos de Datos: Modelos Eloquent y lógica
- ⚙️ Configuración: Configuración Laravel, rutas
- 🗄️ Base de Datos: Migrations, seeders
- 🎨 Recursos Frontend: Vistas, CSS, JS, assets
- 🔧 Comandos Console: Artisan, observers, events
- 📚 Documentación: Docs y guías
- 🧪 Tests: Tests unitarios y de características

## ⚡ SISTEMA DE SINCRONIZACIÓN ZIP

### Perfiles de Sincronización
```bash
# Portal rápido (41KB)
./scripts/portal-master.sh sync zip

# Frontend completo (13.7MB)
./scripts/portal-master.sh sync zip-frontend

# Backend completo (4.1MB)
./scripts/portal-master.sh sync zip-backend

# Proyecto completo (238MB)
./scripts/portal-master.sh sync zip-full
```

### Proceso de Sincronización
1. **Compresión**: Archivos locales → ZIP
2. **Transferencia**: ZIP → Servidor SSH
3. **Extracción**: ZIP → Servidor
4. **Limpieza**: Cache remota + archivos temporales

## 🔄 SISTEMA ASÍNCRONO (NUEVO)

### Comandos Asíncronos
```bash
# Backup asíncrono
php artisan portal:async backup --categories=portal

# Sincronización asíncrona
php artisan portal:async sync --profile=portal

# Monitorear progreso
php artisan portal:async monitor --user=user_12345

# Ver estado
php artisan portal:async status --user=user_12345
```

### Ventajas del Sistema Asíncrono
- ✅ **No bloqueante**: Continúa trabajando mientras se ejecuta
- ✅ **Monitoreo en tiempo real**: Progreso actualizado
- ✅ **Reintentos automáticos**: Si falla, reintenta
- ✅ **Logs detallados**: Registro completo de operaciones

## 🎮 INTERFAZ WEB

### Centro de Soporte Portal
- **Ubicación**: Panel App → Portal Soporte
- **Funciones**:
  - Ejecutar comandos directamente
  - Ver documentación integrada
  - Monitorear estado del sistema
  - Ver logs de ejecución

### Características
- 🎯 **Selección visual**: Menús desplegables y checkboxes
- 📊 **Estado en tiempo real**: Información actualizada del sistema
- 📋 **Documentación integrada**: Guías siempre disponibles
- 🔧 **Ejecución segura**: Comandos validados antes de ejecutar

## 📊 COMPARACIÓN DE SISTEMAS

| Sistema | Velocidad | Control | Facilidad | Seguridad |
|---------|-----------|---------|-----------|-----------|
| **Backup Simple** | Rápido | Bajo | Muy fácil | Media |
| **Backup Interactivo** | Medio | Total | Fácil | Alta |
| **Backup Asíncrono** | Variable | Total | Medio | Alta |
| **Sync Simple** | Medio | Bajo | Fácil | Media |
| **Sync ZIP** | Rápido | Medio | Fácil | Alta |
| **Sync Asíncrono** | Variable | Total | Medio | Alta |

## 🎯 FLUJOS DE TRABAJO RECOMENDADOS

### Para Desarrollo Diario
```bash
# 1. Backup rápido
./scripts/portal-master.sh backup interactive → portal

# 2. Sincronización rápida
./scripts/portal-master.sh sync zip

# 3. Total: ~10 segundos
```

### Para Cambios Importantes
```bash
# 1. Backup completo
./scripts/portal-master.sh backup interactive → portal,models,config

# 2. Sincronización completa
./scripts/portal-master.sh sync zip-frontend

# 3. Total: ~1 minuto
```

### Para Deploy Completo
```bash
# 1. Backup asíncrono
php artisan portal:async backup --categories=all

# 2. Sincronización asíncrona
php artisan portal:async sync --profile=full

# 3. Monitorear ambos
php artisan portal:async monitor --user=user_12345
```

## 🛡️ SEGURIDAD Y MEJORES PRÁCTICAS

### Backups
- ✅ **Automáticos**: Antes de cambios importantes
- ✅ **Categorizados**: Solo lo que necesitas
- ✅ **Versionados**: Con timestamps únicos
- ✅ **Verificados**: Integridad comprobada

### Sincronización
- ✅ **Segura**: SSH con autenticación
- ✅ **Eficiente**: Compresión y transferencia optimizada
- ✅ **Atómica**: Todo o nada
- ✅ **Con rollback**: Backup automático

### Sistema Asíncrono
- ✅ **Reintentos**: Si falla, reintenta automáticamente
- ✅ **Logs**: Registro completo de operaciones
- ✅ **Monitoreo**: Progreso en tiempo real
- ✅ **Notificaciones**: Alertas de éxito/error

## 🆘 SOLUCIÓN DE PROBLEMAS

### SSH Connection Failed
```bash
# Verificar configuración SSH
cat .env | grep SYNC_

# Probar conexión manual
sshpass -p 'PASSWORD' ssh user@server 'echo connection_ok'
```

### Backup Too Large
```bash
# Usar categorías específicas
./scripts/portal-master.sh backup interactive
# Seleccionar solo las categorías necesarias
```

### Sync Slow
```bash
# Usar perfil específico en lugar de full
./scripts/portal-master.sh sync zip  # Portal solo
# o
./scripts/portal-master.sh sync zip-frontend  # Solo frontend
```

### Async Job Failed
```bash
# Ver logs
php artisan queue:failed

# Reintentar trabajo fallido
php artisan queue:retry all
```

## 📚 REFERENCIAS RÁPIDAS

### Comandos Más Usados
```bash
./scripts/portal-master.sh sync zip                    # Sync Portal (5s)
./scripts/portal-master.sh backup interactive          # Backup interactivo
php artisan portal:async backup --categories=portal    # Backup async
php artisan portal:async status                        # Ver estado
```

### Atajos Útiles
```bash
# Portal solo
portal

# Todo
all

# Portal + Modelos + Config
portal,models,config

# Nada
none
```

### Perfiles ZIP
- `portal`: 13 archivos, 41KB
- `frontend`: 812 archivos, 13.7MB
- `backend`: 931 archivos, 4.1MB
- `full`: 3,418 archivos, 238MB

---

**Última actualización**: {{ date('Y-m-d H:i:s') }}
**Versión**: Portal Taxista v2.0
DOC;
    }

    public function getFormSchema(): array
    {
        return [
                Tabs::make('portal_docs')
                    ->tabs([
                        Tabs\Tab::make('Comandos Rápidos')
                            ->schema([
                                Section::make('Backup y Restore')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Placeholder::make('backup_quick')
                                                    ->content('**Backup Rápido Portal**')
                                                    ->columnSpan(1),
                                                Textarea::make('backup_quick_cmd')
                                                    ->label('Comando')
                                                    ->default('./scripts/portal-master.sh backup interactive')
                                                    ->rows(1)
                                                    ->columnSpan(1),

                                                Placeholder::make('backup_async')
                                                    ->content('**Backup Asíncrono**')
                                                    ->columnSpan(1),
                                                Textarea::make('backup_async_cmd')
                                                    ->label('Comando')
                                                    ->default('php artisan portal:async backup --categories=portal')
                                                    ->rows(1)
                                                    ->columnSpan(1),

                                                Placeholder::make('backup_restore')
                                                    ->content('**Restaurar Backup**')
                                                    ->columnSpan(1),
                                                Textarea::make('backup_restore_cmd')
                                                    ->label('Comando')
                                                    ->default('./scripts/portal-master.sh backup restore')
                                                    ->rows(1)
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                Section::make('Sincronización')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Placeholder::make('sync_zip')
                                                    ->content('**Sync ZIP Portal**')
                                                    ->columnSpan(1),
                                                Textarea::make('sync_zip_cmd')
                                                    ->label('Comando')
                                                    ->default('./scripts/portal-master.sh sync zip')
                                                    ->rows(1)
                                                    ->columnSpan(1),

                                                Placeholder::make('sync_async')
                                                    ->content('**Sync Asíncrono**')
                                                    ->columnSpan(1),
                                                Textarea::make('sync_async_cmd')
                                                    ->label('Comando')
                                                    ->default('php artisan portal:async sync --profile=portal')
                                                    ->rows(1)
                                                    ->columnSpan(1),

                                                Placeholder::make('sync_status')
                                                    ->content('**Ver Estado**')
                                                    ->columnSpan(1),
                                                Textarea::make('sync_status_cmd')
                                                    ->label('Comando')
                                                    ->default('php artisan portal:async status')
                                                    ->rows(1)
                                                    ->columnSpan(1),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('Perfiles y Categorías')
                            ->schema([
                                Section::make('Perfiles de Sincronización ZIP')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Placeholder::make('portal_profile')
                                                    ->content('**🚪 Portal Taxista**')
                                                    ->columnSpan(1),
                                                Textarea::make('portal_info')
                                                    ->label('Información')
                                                    ->default('13 archivos, 41KB')
                                                    ->rows(1)
                                                    ->columnSpan(1),

                                                Placeholder::make('frontend_profile')
                                                    ->content('**🎨 Frontend Completo**')
                                                    ->columnSpan(1),
                                                Textarea::make('frontend_info')
                                                    ->label('Información')
                                                    ->default('812 archivos, 13.7MB')
                                                    ->rows(1)
                                                    ->columnSpan(1),

                                                Placeholder::make('backend_profile')
                                                    ->content('**🔧 Backend Completo**')
                                                    ->columnSpan(1),
                                                Textarea::make('backend_info')
                                                    ->label('Información')
                                                    ->default('931 archivos, 4.1MB')
                                                    ->rows(1)
                                                    ->columnSpan(1),

                                                Placeholder::make('full_profile')
                                                    ->content('**📦 Proyecto Completo**')
                                                    ->columnSpan(1),
                                                Textarea::make('full_info')
                                                    ->label('Información')
                                                    ->default('3,418 archivos, 238MB')
                                                    ->rows(1)
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                Section::make('Categorías de Backup')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Placeholder::make('portal_cat')
                                                    ->content('**🚪 Portal Taxista**')
                                                    ->columnSpan(1),
                                                Placeholder::make('livewire_cat')
                                                    ->content('**⚡ Componentes Livewire**')
                                                    ->columnSpan(1),
                                                Placeholder::make('filament_cat')
                                                    ->content('**🎛️ Panel Filament**')
                                                    ->columnSpan(1),

                                                Placeholder::make('models_cat')
                                                    ->content('**📦 Modelos de Datos**')
                                                    ->columnSpan(1),
                                                Placeholder::make('config_cat')
                                                    ->content('**⚙️ Configuración**')
                                                    ->columnSpan(1),
                                                Placeholder::make('database_cat')
                                                    ->content('**🗄️ Base de Datos**')
                                                    ->columnSpan(1),

                                                Placeholder::make('frontend_cat')
                                                    ->content('**🎨 Recursos Frontend**')
                                                    ->columnSpan(1),
                                                Placeholder::make('console_cat')
                                                    ->content('**🔧 Comandos Console**')
                                                    ->columnSpan(1),
                                                Placeholder::make('docs_cat')
                                                    ->content('**📚 Documentación**')
                                                    ->columnSpan(1),

                                                Placeholder::make('tests_cat')
                                                    ->content('**🧪 Tests**')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('Flujos de Trabajo')
                            ->schema([
                                Section::make('Desarrollo Diario')
                                    ->schema([
                                        Textarea::make('daily_workflow')
                                            ->label('Pasos')
                                            ->default('1. ./scripts/portal-master.sh backup interactive → portal
2. ./scripts/portal-master.sh sync zip
3. Total: ~10 segundos')
                                            ->rows(3),
                                    ]),

                                Section::make('Cambios Importantes')
                                    ->schema([
                                        Textarea::make('important_workflow')
                                            ->label('Pasos')
                                            ->default('1. ./scripts/portal-master.sh backup interactive → portal,models,config
2. ./scripts/portal-master.sh sync zip-frontend
3. Total: ~1 minuto')
                                            ->rows(3),
                                    ]),

                                Section::make('Deploy Completo')
                                    ->schema([
                                        Textarea::make('deploy_workflow')
                                            ->label('Pasos')
                                            ->default('1. php artisan portal:async backup --categories=all
2. php artisan portal:async sync --profile=full
3. php artisan portal:async monitor --user=user_12345')
                                            ->rows(3),
                                    ]),
                            ]),

                        Tabs\Tab::make('Solución de Problemas')
                            ->schema([
                                Section::make('Problemas Comunes')
                                    ->schema([
                                        Textarea::make('ssh_issue')
                                            ->label('SSH Connection Failed')
                                            ->default('Solución:
1. Verificar configuración SSH en .env
2. Probar conexión manual con sshpass
3. Revisar que el servidor esté accesible')
                                            ->rows(4),

                                        Textarea::make('backup_large')
                                            ->label('Backup Too Large')
                                            ->default('Solución:
1. Usar backup interactivo
2. Seleccionar categorías específicas
3. Evitar "all" para backups frecuentes')
                                            ->rows(4),

                                        Textarea::make('sync_slow')
                                            ->label('Sync Slow')
                                            ->default('Solución:
1. Usar perfil específico en lugar de full
2. Preferir "sync zip" para cambios rápidos
3. Verificar conexión de red')
                                            ->rows(4),
                                    ]),
                            ]),
                    ]),
        ];
    }
}
