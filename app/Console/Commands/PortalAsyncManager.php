<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\PortalBackupJob;
use App\Jobs\PortalSyncZipJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PortalAsyncManager extends Command
{
    protected $signature = 'portal:async {action} {--profile=default : Sync profile} {--categories=portal : Backup categories} {--user= : User ID for status/monitor}';
    protected $description = 'Async manager for Portal backup and sync operations';

    private array $profiles = [
        'portal' => [
            'name' => '🚪 Portal Taxista',
            'key' => 'portal',
            'paths' => [
                'app/Livewire/PortalTaxistaPro.php',
                'resources/views/livewire/portal-taxista-pro',
                'resources/css/filament/portal',
                'resources/css/portal.css',
                'resources/css/portal-taxista.css',
            ],
            'exclude' => [
                'node_modules',
                'vendor',
                '.git',
                'storage',
                'bootstrap/cache',
            ],
        ],
        'frontend' => [
            'name' => '🎨 Frontend Completo',
            'key' => 'frontend',
            'paths' => [
                'app/Livewire',
                'resources/views',
                'resources/css',
                'resources/js',
                'public/css',
                'public/js',
            ],
            'exclude' => [
                'node_modules',
                'vendor',
                '.git',
                'storage/framework',
                'bootstrap/cache',
            ],
        ],
        'backend' => [
            'name' => '🔧 Backend Completo',
            'key' => 'backend',
            'paths' => [
                'app/Models',
                'app/Filament',
                'app/Http',
                'app/Notifications',
                'app/Observers',
                'app/Enums',
                'app/Console',
                'app/Traits',
                'app/Events',
                'app/Listeners',
                'config',
                'routes',
                'database/migrations',
                'database/seeders',
            ],
            'exclude' => [
                'node_modules',
                'vendor',
                '.git',
                'storage',
                'bootstrap/cache',
            ],
        ],
        'full' => [
            'name' => '📦 Proyecto Completo',
            'key' => 'full',
            'paths' => [
                'app',
                'resources',
                'config',
                'routes',
                'database',
                'public',
                'composer.json',
                'composer.lock',
                'package.json',
                'package-lock.json',
                'vite.config.js',
                'tailwind.config.js',
                'phpunit.xml',
                '.env.example',
            ],
            'exclude' => [
                'node_modules',
                'vendor',
                '.git',
                'storage/logs',
                'storage/framework',
                'bootstrap/cache',
                '.DS_Store',
                '*.log',
            ],
        ],
    ];

    private array $backupCategories = [
        'portal' => [
            'name' => '🚪 Portal Taxista',
            'description' => 'Componentes Livewire, vistas y CSS del Portal',
            'paths' => [
                'app/Livewire/PortalTaxistaPro.php',
                'resources/views/livewire/portal-taxista-pro',
                'resources/css/filament/portal',
                'resources/css/portal.css',
                'resources/css/portal-taxista.css',
            ],
            'enabled' => true,
            'icon' => '🚪',
        ],
        'livewire' => [
            'name' => '⚡ Componentes Livewire',
            'description' => 'Todos los componentes Livewire del sistema',
            'paths' => [
                'app/Livewire',
            ],
            'enabled' => false,
            'icon' => '⚡',
        ],
        'filament' => [
            'name' => '🎛️ Panel Filament',
            'description' => 'Resources, Pages y Components del panel',
            'paths' => [
                'app/Filament',
            ],
            'enabled' => false,
            'icon' => '🎛️',
        ],
        'models' => [
            'name' => '📦 Modelos de Datos',
            'description' => 'Modelos Eloquent y lógica de negocio',
            'paths' => [
                'app/Models',
                'app/Models/Taxi',
            ],
            'enabled' => false,
            'icon' => '📦',
        ],
        'config' => [
            'name' => '⚙️ Configuración',
            'description' => 'Archivos de configuración y rutas',
            'paths' => [
                'config',
                'routes',
                '.env.example',
            ],
            'enabled' => false,
            'icon' => '⚙️',
        ],
        'database' => [
            'name' => '🗄️ Base de Datos',
            'description' => 'Migrations, seeders y estructura',
            'paths' => [
                'database/migrations',
                'database/seeders',
                'database/factories',
            ],
            'enabled' => false,
            'icon' => '🗄️',
        ],
        'frontend' => [
            'name' => '🎨 Recursos Frontend',
            'description' => 'Vistas, CSS, JavaScript y assets',
            'paths' => [
                'resources/views',
                'resources/css',
                'resources/js',
                'public',
            ],
            'enabled' => false,
            'icon' => '🎨',
        ],
        'console' => [
            'name' => '🔧 Comandos Console',
            'description' => 'Comandos Artisan y automatizaciones',
            'paths' => [
                'app/Console/Commands',
                'app/Notifications',
                'app/Observers',
                'app/Events',
                'app/Listeners',
                'app/Traits',
                'app/Enums',
            ],
            'enabled' => false,
            'icon' => '🔧',
        ],
        'docs' => [
            'name' => '📚 Documentación',
            'description' => 'Documentación y guías del proyecto',
            'paths' => [
                'docs',
                'README.md',
                'CHANGELOG.md',
            ],
            'enabled' => false,
            'icon' => '📚',
        ],
        'tests' => [
            'name' => '🧪 Tests',
            'description' => 'Tests unitarios y de características',
            'paths' => [
                'tests',
                'phpunit.xml',
                'pest.php',
            ],
            'enabled' => false,
            'icon' => '🧪',
        ],
    ];

    public function handle(): int
    {
        $action = $this->argument('action');
        
        switch ($action) {
            case 'backup':
                return $this->handleAsyncBackup();
            case 'sync':
                return $this->handleAsyncSync();
            case 'status':
                return $this->showStatus();
            case 'monitor':
                return $this->monitorProgress();
            default:
                $this->error("❌ Acción desconocida: $action");
                $this->info("💡 Acciones disponibles: backup, sync, status, monitor");
                return 1;
        }
    }

    private function handleAsyncBackup(): int
    {
        $categories = $this->parseCategories($this->option('categories'));
        
        $this->info('🚀 Iniciando backup asíncrono...');
        $this->info('📋 Categorías seleccionadas:');
        
        foreach ($categories as $key => $category) {
            if ($category['enabled']) {
                $this->line("  ✅ {$category['icon']} {$category['name']}");
            }
        }
        
        // Generate unique user ID for tracking
        $userId = 'user_' . uniqid();
        
        // Dispatch job
        PortalBackupJob::dispatch($categories, $userId);
        
        $this->newLine();
        $this->info("🎯 Backup iniciado con ID: {$userId}");
        $this->info("📊 Para monitorear el progreso:");
        $this->info("   php artisan portal:async monitor --user={$userId}");
        $this->info("📊 Para ver el estado:");
        $this->info("   php artisan portal:async status --user={$userId}");
        
        return 0;
    }

    private function handleAsyncSync(): int
    {
        $profile = $this->option('profile');
        
        if (!isset($this->profiles[$profile])) {
            $this->error("❌ Perfil '$profile' no encontrado");
            $this->showAvailableProfiles();
            return 1;
        }
        
        $profileInfo = $this->profiles[$profile];
        
        $this->info('🚀 Iniciando sincronización asíncrona...');
        $this->info("📦 Perfil: {$profileInfo['name']}");
        
        // Generate unique user ID for tracking
        $userId = 'user_' . uniqid();
        
        // Dispatch job
        PortalSyncZipJob::dispatch($profileInfo, $userId);
        
        $this->newLine();
        $this->info("🎯 Sincronización iniciada con ID: {$userId}");
        $this->info("📊 Para monitorear el progreso:");
        $this->info("   php artisan portal:async monitor --user={$userId}");
        $this->info("📊 Para ver el estado:");
        $this->info("   php artisan portal:async status --user={$userId}");
        
        return 0;
    }

    private function showStatus(): int
    {
        $userId = $this->option('user');
        
        if (!$userId) {
            $this->showLatestStatus();
        } else {
            $this->showUserStatus($userId);
        }
        
        return 0;
    }

    private function monitorProgress(): int
    {
        $userId = $this->option('user');
        
        if (!$userId) {
            $this->error("❌ Se requiere --user=ID para monitorear");
            $this->info("💡 Obtén el ID de un comando anterior de backup/sync");
            return 1;
        }
        
        $this->info("📊 Monitoreando progreso para: {$userId}");
        $this->newLine();
        
        while (true) {
            $progress = Cache::get("backup_progress_{$userId}") ?? Cache::get("sync_progress_{$userId}");
            $complete = Cache::get("backup_complete_{$userId}") ?? Cache::get("sync_complete_{$userId}");
            $failed = Cache::get("backup_failed_{$userId}") ?? Cache::get("sync_failed_{$userId}");
            
            if ($complete) {
                $profile = $complete['profile'] ?? 'Backup';
                $this->info("✅ Completado: {$profile}");
                $this->info("📅 Finalizado: {$complete['completed_at']}");
                break;
            }
            
            if ($failed) {
                $this->error("❌ Falló: {$failed['error']}");
                $this->error("📅 Falló: {$failed['failed_at']}");
                break;
            }
            
            if ($progress) {
                $this->line("\r" . str_repeat(' ', 50) . "\r", false);
                $this->line("📊 Progreso: {$progress['progress']}% - {$progress['status']}", false);
                
                if (isset($progress['profile'])) {
                    $this->line(" ({$progress['profile']})", false);
                }
            } else {
                $this->line("\r" . str_repeat(' ', 50) . "\r", false);
                $this->line("⏳ Esperando inicio del proceso...", false);
            }
            
            usleep(500000); // 0.5 seconds
        }
        
        $this->newLine(2);
        $this->info("🎉 Monitoreo completado");
        
        return 0;
    }

    private function parseCategories(string $categoriesString): array
    {
        $selected = explode(',', $categoriesString);
        $selected = array_map('trim', $selected);
        
        foreach ($this->backupCategories as $key => &$category) {
            $category['enabled'] = in_array($key, $selected);
        }
        
        return $this->backupCategories;
    }

    private function showAvailableProfiles(): void
    {
        $this->info('📋 Perfiles disponibles:');
        foreach ($this->profiles as $key => $profile) {
            $this->line("  • $key: {$profile['name']}");
        }
    }

    private function showLatestStatus(): void
    {
        $this->info('📊 Estado más reciente:');
        
        // Show latest backup
        $backupComplete = Cache::get('backup_complete_user_' . $this->getLatestUserId('backup'));
        $backupFailed = Cache::get('backup_failed_user_' . $this->getLatestUserId('backup'));
        
        if ($backupComplete) {
            $this->info("✅ Backup completado: {$backupComplete['filename']}");
            $this->info("   📁 {$backupComplete['files']} archivos, {$backupComplete['size']}");
            $this->info("   📅 {$backupComplete['completed_at']}");
        } elseif ($backupFailed) {
            $this->error("❌ Backup falló: {$backupFailed['error']}");
        } else {
            $this->line("⏳ Sin backups recientes");
        }
        
        $this->newLine();
        
        // Show latest sync
        $syncComplete = Cache::get('sync_complete_user_' . $this->getLatestUserId('sync'));
        $syncFailed = Cache::get('sync_failed_user_' . $this->getLatestUserId('sync'));
        
        if ($syncComplete) {
            $this->info("✅ Sync completado: {$syncComplete['profile']}");
            $this->info("   📅 {$syncComplete['completed_at']}");
        } elseif ($syncFailed) {
            $this->error("❌ Sync falló: {$syncFailed['error']}");
        } else {
            $this->line("⏳ Sin sincronizaciones recientes");
        }
    }

    private function showUserStatus(string $userId): void
    {
        $this->info("📊 Estado para: {$userId}");
        
        $backupProgress = Cache::get("backup_progress_{$userId}");
        $backupComplete = Cache::get("backup_complete_{$userId}");
        $backupFailed = Cache::get("backup_failed_{$userId}");
        
        if ($backupComplete) {
            $this->info("✅ Backup completado: {$backupComplete['filename']}");
            $this->info("   📁 {$backupComplete['files']} archivos, {$backupComplete['size']}");
        } elseif ($backupFailed) {
            $this->error("❌ Backup falló: {$backupFailed['error']}");
        } elseif ($backupProgress) {
            $this->info("🔄 Backup en progreso: {$backupProgress['progress']}%");
        } else {
            $this->line("⏳ Sin actividad de backup");
        }
        
        $this->newLine();
        
        $syncProgress = Cache::get("sync_progress_{$userId}");
        $syncComplete = Cache::get("sync_complete_{$userId}");
        $syncFailed = Cache::get("sync_failed_{$userId}");
        
        if ($syncComplete) {
            $this->info("✅ Sync completado: {$syncComplete['profile']}");
        } elseif ($syncFailed) {
            $this->error("❌ Sync falló: {$syncFailed['error']}");
        } elseif ($syncProgress) {
            $this->info("🔄 Sync en progreso: {$syncProgress['progress']}%");
        } else {
            $this->line("⏳ Sin actividad de sync");
        }
    }

    private function getLatestUserId(string $type): ?string
    {
        // This is a simplified approach - in production you might want to store this in database
        $keys = Cache::getRedis()->keys("*{$type}_complete_user_*");
        
        if (!empty($keys)) {
            $latestKey = max($keys);
            preg_match("/{$type}_complete_user_(.+)$/", $latestKey, $matches);
            return $matches[1] ?? null;
        }
        
        return null;
    }
}
