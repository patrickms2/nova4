<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;

class PortalBackupInteractive extends Command
{
    protected $signature = 'portal:backup-interactive {--restore : Restore from backup}';
    protected $description = 'Interactive backup system with checkbox selection for Portal Taxista';

    private array $config;
    private string $backupPath;

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
            'enabled' => true, // Portal por defecto
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
            'name' => '🎛️  Panel Filament',
            'description' => 'Resources, Pages y Components del panel de administración',
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
            'name' => '⚙️  Configuración',
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
            'name' => '🗄️  Base de Datos',
            'description' => 'Migrations, seeders y estructura de la base de datos',
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
            'description' => 'Vistas, CSS, JavaScript y assets públicos',
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
            'description' => 'Comandos Artisan y tareas automatizadas',
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
            'description' => 'Documentación del proyecto y guías',
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
        $this->loadConfig();
        
        if ($this->option('restore')) {
            return $this->interactiveRestore();
        }
        
        return $this->interactiveBackup();
    }

    private function loadConfig(): void
    {
        $this->backupPath = storage_path('backups/portal');
        File::ensureDirectoryExists($this->backupPath);
    }

    private function interactiveBackup(): int
    {
        $this->displayHeader();
        
        // Mostrar menú de selección
        $this->showSelectionMenu();
        
        // Procesar selección del usuario
        $this->processUserSelection();
        
        // Mostrar resumen
        $this->showBackupSummary();
        
        // Confirmar y ejecutar backup
        if ($this->confirm('📤 ¿Deseas crear el backup con estas opciones?', true)) {
            return $this->executeBackup();
        }
        
        $this->info('❌ Backup cancelado');
        return 0;
    }

    private function displayHeader(): void
    {
        $this->newLine();
        $this->info('┌─────────────────────────────────────────────────────────────┐');
        $this->info('│                🗄️  TAXILANZ PORTAL BACKUP                  │');
        $this->info('│               Sistema Interactivo de Backup                │');
        $this->info('└─────────────────────────────────────────────────────────────┘');
        $this->newLine();
    }

    private function showSelectionMenu(): void
    {
        $this->info('📋 SELECCIONA LAS CATEGORÍAS A INCLUIR EN EL BACKUP:');
        $this->newLine();
        
        foreach ($this->backupCategories as $key => $category) {
            $checkbox = $category['enabled'] ? '✅' : '⭕';
            $this->line("  {$checkbox} [{$key}] {$category['icon']} {$category['name']}");
            $this->line("      {$category['description']}");
            $this->newLine();
        }
        
        $this->info('💡 Instrucciones:');
        $this->info('  • Escribe los nombres de las categorías separados por coma (ej: portal,models)');
        $this->info('  • Escribe "all" para seleccionar todo');
        $this->info('  • Escribe "portal" para solo el Portal (recomendado)');
        $this->info('  • Escribe "none" para deseleccionar todo');
        $this->info('  • Presiona Enter para mantener la selección actual');
        $this->newLine();
    }

    private function processUserSelection(): void
    {
        $selection = $this->ask('📝 ¿Qué categorías quieres incluir en el backup?', 'portal');
        
        if ($selection) {
            $this->updateSelection($selection);
        }
        
        $this->showUpdatedSelection();
    }

    private function updateSelection(string $selection): void
    {
        $selection = strtolower(trim($selection));

        if ($selection === 'all') {
            foreach ($this->backupCategories as $key => &$category) {
                $category['enabled'] = true;
            }
            return;
        }

        if ($selection === 'none') {
            foreach ($this->backupCategories as $key => &$category) {
                $category['enabled'] = false;
            }
            return;
        }

        if ($selection === 'portal') {
            foreach ($this->backupCategories as $key => &$category) {
                $category['enabled'] = $key === 'portal';
            }
            return;
        }

        $selected = array_map('trim', explode(',', $selection));
        
        foreach ($this->backupCategories as $key => &$category) {
            $category['enabled'] = in_array($key, $selected);
        }
    }

    private function showUpdatedSelection(): void
    {
        $this->newLine();
        $this->info('📊 Categorías seleccionadas:');
        
        $selected = array_filter($this->backupCategories, fn($cat) => $cat['enabled']);
        $totalFiles = 0;
        $totalSize = 0;
        
        foreach ($selected as $key => $category) {
            $fileCount = $this->getCategoryFileCount($key);
            $size = $this->getCategorySize($key);
            $totalFiles += $fileCount;
            $totalSize += $size;
            
            $this->line("  ✅ {$category['icon']} {$category['name']}: {$fileCount} archivos, " . $this->formatBytes($size));
        }
        
        if (empty($selected)) {
            $this->warn('  ⚠️  No hay categorías seleccionadas');
            exit(1);
        }
        
        $this->newLine();
        $this->info("📈 Total estimado: {$totalFiles} archivos, " . $this->formatBytes($totalSize));
        $this->newLine();
    }

    private function showBackupSummary(): void
    {
        $this->info('📋 RESUMEN DEL BACKUP:');
        $this->newLine();
        
        $selected = array_filter($this->backupCategories, fn($cat) => $cat['enabled']);
        
        foreach ($selected as $key => $category) {
            $this->line("  ✅ {$category['icon']} {$category['name']}");
            foreach ($category['paths'] as $path) {
                $this->line("      📁 {$path}");
            }
        }
        
        $this->newLine();
    }

    private function executeBackup(): int
    {
        $this->info('🚀 Creando backup...');
        
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupFile = $this->backupPath . "/portal_backup_{$timestamp}.zip";
        
        // Crear ZIP
        $zip = new ZipArchive();
        if ($zip->open($backupFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            $this->error('❌ No se pudo crear el archivo ZIP');
            return 1;
        }
        
        $addedFiles = 0;
        $progressBar = $this->output->createProgressBar(100);
        $progressBar->start();
        
        foreach ($this->backupCategories as $key => $category) {
            if (!$category['enabled']) {
                continue;
            }
            
            foreach ($category['paths'] as $path) {
                $fullPath = base_path($path);
                
                if (File::isFile($fullPath)) {
                    $zip->addFile($fullPath, $path);
                    $addedFiles++;
                } elseif (File::isDirectory($fullPath)) {
                    $this->addDirectoryToZip($zip, $fullPath, $path, $addedFiles);
                }
            }
            
            $progressBar->advance(10); // Progreso aproximado
        }
        
        $zip->close();
        $progressBar->finish();
        $this->newLine();
        
        $backupSize = File::size($backupFile);
        
        $this->newLine();
        $this->info("✅ Backup creado exitosamente:");
        $this->info("   📁 Archivo: " . basename($backupFile));
        $this->info("   📊 Archivos: {$addedFiles}");
        $this->info("   💾 Tamaño: " . $this->formatBytes($backupSize));
        $this->info("   📍 Ubicación: {$backupFile}");
        
        return 0;
    }

    private function addDirectoryToZip(ZipArchive $zip, string $dir, string $baseDir, int &$addedFiles): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = $baseDir . '/' . str_replace($dir . '/', '', $file->getPathname());
                $zip->addFile($file->getPathname(), $relativePath);
                $addedFiles++;
            }
        }
    }

    private function interactiveRestore(): int
    {
        $this->displayHeader();
        $this->info('🔄 RESTAURAR DESDE BACKUP');
        $this->newLine();
        
        $backups = $this->listAvailableBackups();
        
        if (empty($backups)) {
            $this->warn('⚠️  No hay backups disponibles');
            return 0;
        }
        
        $selectedBackup = $this->choice(
            '📂 Selecciona el backup a restaurar:',
            $backups,
            0
        );
        
        $backupFile = $this->backupPath . '/' . $selectedBackup;
        
        $this->info("📁 Backup seleccionado: {$selectedBackup}");
        $this->info("📊 Tamaño: " . $this->formatBytes(File::size($backupFile)));
        
        if (!$this->confirm('⚠️  Esto sobrescribirá archivos locales. ¿Continuar?', false)) {
            $this->info('❌ Restauración cancelada');
            return 0;
        }
        
        return $this->executeRestore($backupFile);
    }

    private function listAvailableBackups(): array
    {
        $files = File::files($this->backupPath);
        $backups = [];
        
        foreach ($files as $file) {
            if (str_contains($file->getFilename(), 'portal_backup_') && str_ends_with($file->getFilename(), '.zip')) {
                $backups[] = $file->getFilename();
            }
        }
        
        rsort($backups); // Más recientes primero
        return $backups;
    }

    private function executeRestore(string $backupFile): int
    {
        $this->info('🔄 Restaurando backup...');
        
        // Crear backup de seguridad antes de restaurar
        $safetyBackup = $this->backupPath . "/safety_backup_" . now()->format('Y-m-d_H-i-s') . ".zip";
        $this->createSafetyBackup($safetyBackup);
        
        // Extraer backup
        $zip = new ZipArchive();
        if ($zip->open($backupFile) !== TRUE) {
            $this->error('❌ No se pudo abrir el archivo ZIP');
            return 1;
        }
        
        $extractedFiles = $zip->count();
        $zip->extractTo(base_path());
        $zip->close();
        
        $this->info("✅ Backup restaurado exitosamente:");
        $this->info("   📁 Archivos restaurados: {$extractedFiles}");
        $this->info("   🔒 Backup de seguridad: " . basename($safetyBackup));
        
        // Limpiar caches
        $this->call('optimize:clear');
        
        return 0;
    }

    private function createSafetyBackup(string $backupFile): void
    {
        $this->info('🔒 Creando backup de seguridad...');
        
        $zip = new ZipArchive();
        $zip->open($backupFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        
        // Solo backup de archivos críticos para seguridad
        $criticalPaths = [
            'app/Livewire',
            'app/Filament',
            'app/Models',
            'resources/views',
            'resources/css',
            'config',
            'routes',
        ];
        
        foreach ($criticalPaths as $path) {
            $fullPath = base_path($path);
            if (File::isDirectory($fullPath)) {
                $this->addDirectoryToZip($zip, $fullPath, $path, $addedFiles);
            }
        }
        
        $zip->close();
    }

    private function getCategoryFileCount(string $categoryKey): int
    {
        $category = $this->backupCategories[$categoryKey];
        $count = 0;
        
        foreach ($category['paths'] as $path) {
            $fullPath = base_path($path);
            
            if (File::isFile($fullPath)) {
                $count++;
            } elseif (File::isDirectory($fullPath)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $count++;
                    }
                }
            }
        }
        
        return $count;
    }

    private function getCategorySize(string $categoryKey): int
    {
        $category = $this->backupCategories[$categoryKey];
        $size = 0;
        
        foreach ($category['paths'] as $path) {
            $fullPath = base_path($path);
            
            if (File::isFile($fullPath)) {
                $size += File::size($fullPath);
            } elseif (File::isDirectory($fullPath)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $size += $file->getSize();
                    }
                }
            }
        }
        
        return $size;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
