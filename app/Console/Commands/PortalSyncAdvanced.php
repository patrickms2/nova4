<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PortalSyncAdvanced extends Command
{
    protected $signature = 'portal:sync-advanced {--upload : Upload to server} {--download : Download from server} {--interactive : Interactive mode with checkboxes}';
    protected $description = 'Advanced sync with category selection for Portal Taxista';

    private array $config;
    private string $remoteHost;
    private string $remoteUser;
    private string $remotePath;
    private string $localPath;

    private array $categories = [
        'models' => [
            'name' => '📦 Modelos Eloquent',
            'description' => 'Modelos de base de datos (User, Taxi*, etc)',
            'paths' => [
                'app/Models',
                'app/Models/Taxi',
            ],
            'extensions' => ['php'],
            'enabled' => false,
        ],
        'filament' => [
            'name' => '🎛️  Controladores Filament',
            'description' => 'Resources, Pages y Components de Filament',
            'paths' => [
                'app/Filament',
            ],
            'extensions' => ['php'],
            'enabled' => false,
        ],
        'livewire' => [
            'name' => '⚡ Livewire Components',
            'description' => 'Componentes Livewire del Portal',
            'paths' => [
                'app/Livewire',
            ],
            'extensions' => ['php'],
            'enabled' => false,
        ],
        'core' => [
            'name' => '🔧 Core Laravel',
            'description' => 'Config, Notifications, Observers, Enums, Console, Traits, Events, Routes',
            'paths' => [
                'config',
                'app/Notifications',
                'app/Observers',
                'app/Enums',
                'app/Console/Commands',
                'app/Traits',
                'app/Events',
                'app/Listeners',
                'routes',
            ],
            'extensions' => ['php'],
            'enabled' => false,
        ],
        'resources' => [
            'name' => '🎨 Resources (CSS, JS, Vistas)',
            'description' => 'Archivos frontend: CSS, JavaScript, Blade views',
            'paths' => [
                'resources/views',
                'resources/css',
                'resources/js',
            ],
            'extensions' => ['php', 'css', 'js', 'blade.php'],
            'enabled' => false,
        ],
        'portal' => [
            'name' => '🚪 Portal Taxista (Recomendado)',
            'description' => 'Archivos críticos del Portal Taxista',
            'paths' => [
                'app/Livewire/PortalTaxistaPro.php',
                'resources/views/livewire/portal-taxista-pro',
                'resources/css/filament/portal',
                'resources/css/portal.css',
                'resources/css/portal-taxista.css',
            ],
            'extensions' => ['php', 'css', 'blade.php'],
            'enabled' => true, // Portal enabled by default
        ],
        'database' => [
            'name' => '🗄️  Database',
            'description' => 'Migrations, Seeders, Factories',
            'paths' => [
                'database/migrations',
                'database/seeders',
                'database/factories',
            ],
            'extensions' => ['php'],
            'enabled' => false,
        ],
        'tests' => [
            'name' => '🧪 Tests',
            'description' => 'Unit y Feature tests (Pest, PHPUnit)',
            'paths' => [
                'tests',
            ],
            'extensions' => ['php'],
            'enabled' => false,
        ],
    ];

    public function handle(): int
    {
        $this->loadConfig();
        
        if ($this->option('interactive')) {
            $this->showInteractiveMenu();
            return $this->executeSync();
        }

        if ($this->option('upload')) {
            return $this->upload();
        }

        if ($this->option('download')) {
            return $this->download();
        }

        return $this->status();
    }

    private function loadConfig(): void
    {
        $this->config = [
            'host' => env('SYNC_SSH_HOST'),
            'user' => env('SYNC_SSH_USER'),
            'password' => env('SYNC_SSH_PASSWORD'),
            'port' => env('SYNC_SSH_PORT', 22),
            'remote_path' => env('SYNC_REMOTE_PATH', '/var/www/nova'),
            'local_path' => base_path(),
        ];

        $this->remoteHost = $this->config['host'];
        $this->remoteUser = $this->config['user'];
        $this->remotePath = $this->config['remote_path'];
        $this->localPath = $this->config['local_path'];

        if (!$this->remoteHost || !$this->remoteUser) {
            $this->error('❌ SSH configuration missing in .env file');
            exit(1);
        }

        $this->info("🔗 SSH Config: {$this->remoteUser}@{$this->remoteHost}:{$this->config['port']}");
        $this->info("📁 Remote: {$this->remotePath}");
        $this->info("📁 Local: {$this->localPath}");
    }

    private function showInteractiveMenu(): void
    {
        $this->info('🎯 SELECCIÓN DE CATEGORÍAS DE SINCRONIZACIÓN');
        $this->info('─────────────────────────────────────────────────────');

        foreach ($this->categories as $key => $category) {
            $status = $category['enabled'] ? '✅' : '⭕';
            $this->line("{$status} [{$key}] {$category['name']}");
            $this->line("     {$category['description']}");
            $this->line('');
        }

        $this->info('💡 Instrucciones:');
        $this->info('  • Escribe los nombres de las categorías separados por coma');
        $this->info('  • Ejemplo: portal,models,resources');
        $this->info('  • Escribe "all" para seleccionar todo');
        $this->info('  • Escribe "portal" para solo archivos del Portal');
        $this->info('  • Presiona Enter para mantener la selección actual');

        $selection = $this->ask('📝 ¿Qué categorías quieres sincronizar?');

        if ($selection) {
            $this->processSelection($selection);
        }

        $this->showSelectedCategories();
    }

    private function processSelection(string $selection): void
    {
        $selection = strtolower(trim($selection));

        if ($selection === 'all') {
            foreach ($this->categories as $key => &$category) {
                $category['enabled'] = true;
            }
            return;
        }

        if ($selection === 'portal') {
            foreach ($this->categories as $key => &$category) {
                $category['enabled'] = $key === 'portal';
            }
            return;
        }

        $selected = array_map('trim', explode(',', $selection));
        
        foreach ($this->categories as $key => &$category) {
            $category['enabled'] = in_array($key, $selected);
        }
    }

    private function showSelectedCategories(): void
    {
        $this->info('📋 Categorías seleccionadas:');
        $selected = array_filter($this->categories, fn($cat) => $cat['enabled']);
        
        foreach ($selected as $key => $category) {
            $this->info("  ✅ {$category['name']}");
        }
        
        if (empty($selected)) {
            $this->warn('⚠️  No hay categorías seleccionadas');
            exit(1);
        }
        
        $this->info('');
    }

    private function getFilesToSync(): array
    {
        $files = [];
        
        foreach ($this->categories as $key => $category) {
            if (!$category['enabled']) {
                continue;
            }

            foreach ($category['paths'] as $path) {
                $fullPath = $this->localPath . '/' . $path;
                
                if (File::isFile($fullPath)) {
                    // Single file
                    if (in_array(pathinfo($fullPath, PATHINFO_EXTENSION), $category['extensions'])) {
                        $files[] = $path;
                    }
                } elseif (File::isDirectory($fullPath)) {
                    // Directory - get all matching files
                    $iterator = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS)
                    );

                    foreach ($iterator as $file) {
                        if ($file->isFile() && in_array($file->getExtension(), $category['extensions'])) {
                            $relativePath = str_replace($this->localPath . '/', '', $file->getPathname());
                            $files[] = $relativePath;
                        }
                    }
                }
            }
        }

        return array_unique($files);
    }

    private function upload(): int
    {
        $files = $this->getFilesToSync();
        
        if (empty($files)) {
            $this->warn('⚠️  No hay archivos para sincronizar');
            return 0;
        }

        $this->info('📤 Uploading files...');
        $this->info('📊 Total files: ' . count($files));

        $uploadedFiles = 0;
        $failedFiles = 0;
        $progressBar = $this->output->createProgressBar(count($files));
        $progressBar->start();

        foreach ($files as $file) {
            $localFile = $this->localPath . '/' . $file;
            $remoteFile = $this->remoteUser . '@' . $this->remoteHost . ':' . $this->remotePath . '/' . $file;

            if (!File::exists($localFile)) {
                $failedFiles++;
                $progressBar->advance();
                continue;
            }

            // Create remote directory if needed
            $remoteDir = dirname($this->remotePath . '/' . $file);
            $mkdirCommand = "sshpass -p '{$this->config['password']}' ssh -t -p {$this->config['port']} -o StrictHostKeyChecking=no {$this->remoteUser}@{$this->remoteHost} 'cd /var/www/html/taxilanzhr/taxilanzhr && mkdir -p \"$remoteDir\"'";
            shell_exec($mkdirCommand);

            // Upload file using scp
            $scpCommand = "sshpass -p '{$this->config['password']}' scp -P {$this->config['port']} -o StrictHostKeyChecking=no \"$localFile\" \"$remoteFile\"";
            
            $output = shell_exec($scpCommand . ' 2>&1');
            
            if ($output === null || str_contains($output ?? '', '100%')) {
                $uploadedFiles++;
            } else {
                $failedFiles++;
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->info("📊 Upload Summary:");
        $this->info("   ✅ Uploaded: $uploadedFiles files");
        $this->info("   ❌ Failed: $failedFiles files");

        if ($uploadedFiles > 0) {
            $this->clearRemoteCaches();
        }

        return $failedFiles === 0 ? 0 : 1;
    }

    private function download(): int
    {
        $files = $this->getFilesToSync();
        
        if (empty($files)) {
            $this->warn('⚠️  No hay archivos para descargar');
            return 0;
        }

        if (!$this->confirm('⚠️  This will overwrite local files. Continue?')) {
            $this->info('❌ Operation cancelled');
            return 0;
        }

        $this->info('📥 Downloading files...');
        $this->info('📊 Total files: ' . count($files));

        $downloadedFiles = 0;
        $failedFiles = 0;
        $progressBar = $this->output->createProgressBar(count($files));
        $progressBar->start();

        foreach ($files as $file) {
            $localFile = $this->localPath . '/' . $file;
            $remoteFile = $this->remoteUser . '@' . $this->remoteHost . ':' . $this->remotePath . '/' . $file;

            // Create local directory if needed
            $localDir = dirname($localFile);
            if (!File::exists($localDir)) {
                File::ensureDirectoryExists($localDir);
            }

            // Download file using scp
            $scpCommand = "sshpass -p '{$this->config['password']}' scp -P {$this->config['port']} -o StrictHostKeyChecking=no \"$remoteFile\" \"$localFile\"";
            
            $output = shell_exec($scpCommand . ' 2>&1');
            
            if ($output === null || str_contains($output ?? '', '100%')) {
                $downloadedFiles++;
            } else {
                $failedFiles++;
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->info("📊 Download Summary:");
        $this->info("   ✅ Downloaded: $downloadedFiles files");
        $this->info("   ❌ Failed: $failedFiles files");

        if ($downloadedFiles > 0) {
            // Clear local caches
            $this->call('optimize:clear');
        }

        return $failedFiles === 0 ? 0 : 1;
    }

    private function status(): int
    {
        $this->info('📊 Sync Status');
        
        foreach ($this->categories as $key => $category) {
            $status = $category['enabled'] ? '✅' : '⭕';
            $fileCount = $this->getCategoryFileCount($key);
            $this->line("{$status} {$category['name']}: {$fileCount} archivos");
        }
        
        return 0;
    }

    private function getCategoryFileCount(string $categoryKey): int
    {
        $category = $this->categories[$categoryKey];
        $count = 0;
        
        foreach ($category['paths'] as $path) {
            $fullPath = $this->localPath . '/' . $path;
            
            if (File::isFile($fullPath)) {
                if (in_array(pathinfo($fullPath, PATHINFO_EXTENSION), $category['extensions'])) {
                    $count++;
                }
            } elseif (File::isDirectory($fullPath)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile() && in_array($file->getExtension(), $category['extensions'])) {
                        $count++;
                    }
                }
            }
        }
        
        return $count;
    }

    private function executeSync(): int
    {
        $action = $this->choice(
            '🚀 ¿Qué acción quieres realizar?',
            ['upload' => '📤 Subir archivos al servidor', 'download' => '📥 Descargar archivos del servidor'],
            'upload'
        );

        return $action === 'upload' ? $this->upload() : $this->download();
    }

    private function clearRemoteCaches(): void
    {
        $this->info('🧹 Clearing remote caches...');
        
        $commands = [
            "cd {$this->remotePath} && php artisan optimize:clear",
            "cd {$this->remotePath} && php artisan view:clear",
            "cd {$this->remotePath} && php artisan config:clear",
            "cd {$this->remotePath} && php artisan route:clear",
        ];

        foreach ($commands as $command) {
            $sshCommand = "sshpass -p '{$this->config['password']}' ssh -t -p {$this->config['port']} -o StrictHostKeyChecking=no {$this->remoteUser}@{$this->remoteHost} '$command'";
            
            $output = shell_exec($sshCommand . ' 2>&1');
            
            if ($output === null || !str_contains($output, 'Error')) {
                $this->info('✅ Remote cache cleared');
            } else {
                $this->warn('⚠️  Could not clear remote cache');
            }
        }
    }
}
