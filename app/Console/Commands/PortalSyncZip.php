<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;

class PortalSyncZip extends Command
{
    protected $signature = 'portal:sync-zip {--profile=default : Sync profile (portal|frontend|backend|full|custom)} {--upload : Upload to server} {--download : Download from server}';
    protected $description = 'Fast ZIP-based sync with predefined profiles for Portal Taxista';

    private array $config;
    private string $remoteHost;
    private string $remoteUser;
    private string $remotePath;
    private string $localPath;

    private array $profiles = [
        'portal' => [
            'name' => '🚪 Portal Taxista',
            'description' => 'Archivos críticos del Portal (rápido)',
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
            'description' => 'Portal + todos los recursos visuales',
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
            'description' => 'Modelos, controladores, config, etc',
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
            'description' => 'Todo excepto archivos grandes/temporales',
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

    public function handle(): int
    {
        $this->loadConfig();
        
        $profile = $this->option('profile');
        
        if (!isset($this->profiles[$profile])) {
            $this->error("❌ Profile '$profile' not found");
            $this->showAvailableProfiles();
            return 1;
        }

        if ($this->option('upload')) {
            return $this->upload($profile);
        }

        if ($this->option('download')) {
            return $this->download($profile);
        }

        return $this->showProfileInfo($profile);
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
    }

    private function showAvailableProfiles(): void
    {
        $this->info('📋 Available Profiles:');
        foreach ($this->profiles as $key => $profile) {
            $this->line("  • $key: {$profile['name']} - {$profile['description']}");
        }
    }

    private function showProfileInfo(string $profile): int
    {
        $profileInfo = $this->profiles[$profile];
        
        $this->info("📊 Profile: {$profileInfo['name']}");
        $this->info("📝 {$profileInfo['description']}");
        $this->info('');
        
        $totalFiles = 0;
        $totalSize = 0;
        
        foreach ($profileInfo['paths'] as $path) {
            $fullPath = $this->localPath . '/' . $path;
            
            if (File::isFile($fullPath)) {
                $totalFiles++;
                $totalSize += File::size($fullPath);
            } elseif (File::isDirectory($fullPath)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile() && !$this->shouldExclude($file->getPathname(), $profileInfo['exclude'])) {
                        $totalFiles++;
                        $totalSize += $file->getSize();
                    }
                }
            }
        }
        
        $this->info("📁 Files: $totalFiles");
        $this->info("💾 Size: " . $this->formatBytes($totalSize));
        $this->info('');
        $this->info("🚀 To sync: php artisan portal:sync-zip --profile=$profile --upload");
        
        return 0;
    }

    private function upload(string $profile): int
    {
        $profileInfo = $this->profiles[$profile];
        
        $this->info("🚀 Uploading with profile: {$profileInfo['name']}");
        
        // Create ZIP
        $zipFile = $this->createZip($profile);
        
        if (!$zipFile) {
            $this->error('❌ Failed to create ZIP file');
            return 1;
        }
        
        $zipSize = File::size($zipFile);
        $this->info("📦 ZIP created: " . basename($zipFile) . " (" . $this->formatBytes($zipSize) . ")");
        
        // Upload ZIP to server
        $this->info("📤 Uploading ZIP to server...");
        if ($this->uploadZip($zipFile)) {
            $this->info("✅ ZIP uploaded successfully");
            
            // Extract on server
            $this->info("📂 Extracting on server...");
            if ($this->extractZipOnServer(basename($zipFile))) {
                $this->info("✅ Extracted successfully");
                
                // Clear remote caches
                $this->clearRemoteCaches();
                
                // Clean up local ZIP
                File::delete($zipFile);
                
                $this->info("🎉 Sync completed successfully!");
                return 0;
            } else {
                $this->error("❌ Failed to extract on server");
                return 1;
            }
        } else {
            $this->error("❌ Failed to upload ZIP");
            return 1;
        }
    }

    private function createZip(string $profile): ?string
    {
        $profileInfo = $this->profiles[$profile];
        $timestamp = now()->format('Y-m-d_H-i-s');
        $zipPath = storage_path("app/sync/sync_{$profile}_{$timestamp}.zip");
        
        File::ensureDirectoryExists(dirname($zipPath));
        
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            return null;
        }
        
        $addedFiles = 0;
        $progressBar = $this->output->createProgressBar(100);
        $progressBar->start();
        
        foreach ($profileInfo['paths'] as $path) {
            $fullPath = $this->localPath . '/' . $path;
            
            if (File::isFile($fullPath)) {
                if (!$this->shouldExclude($fullPath, $profileInfo['exclude'])) {
                    $relativePath = $path;
                    $zip->addFile($fullPath, $relativePath);
                    $addedFiles++;
                }
            } elseif (File::isDirectory($fullPath)) {
                $this->addDirectoryToZip($zip, $fullPath, $path, $profileInfo['exclude'], $addedFiles);
            }
            
            $progressBar->advance(25); // Approximate progress
        }
        
        $zip->close();
        $progressBar->finish();
        $this->newLine();
        
        $this->info("📁 Added $addedFiles files to ZIP");
        
        return $zipPath;
    }

    private function addDirectoryToZip(ZipArchive $zip, string $dir, string $baseDir, array $exclude, int &$addedFiles): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && !$this->shouldExclude($file->getPathname(), $exclude)) {
                $relativePath = $baseDir . '/' . str_replace($dir . '/', '', $file->getPathname());
                $zip->addFile($file->getPathname(), $relativePath);
                $addedFiles++;
            }
        }
    }

    private function shouldExclude(string $path, array $exclude): bool
    {
        foreach ($exclude as $pattern) {
            if (str_contains($path, $pattern)) {
                return true;
            }
            
            // Handle glob patterns
            if (str_contains($pattern, '*')) {
                $regex = '/^' . str_replace('*', '.*', preg_quote($pattern, '/')) . '$/';
                if (preg_match($regex, $path)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    private function uploadZip(string $zipPath): bool
    {
        $remoteZipPath = "/tmp/" . basename($zipPath);
        $command = "sshpass -p '{$this->config['password']}' scp -P {$this->config['port']} -o StrictHostKeyChecking=no \"$zipPath\" {$this->remoteUser}@{$this->remoteHost}:\"$remoteZipPath\"";
        
        $output = shell_exec($command . ' 2>&1');
        
        return $output === null || str_contains($output ?? '', '100%');
    }

    private function extractZipOnServer(string $zipFilename): bool
    {
        $remoteZipPath = "/tmp/$zipFilename";
        
        // Create backup and extract commands
        $commands = [
            "cd /var/www/html/taxilanzhr/taxilanzhr",
            "mkdir -p backup_$(date +%Y%m%d_%H%M%S)",
            "cp -r * backup_$(date +%Y%m%d_%H%M%S)/ 2>/dev/null || true",
            "unzip -o '$remoteZipPath' -d .",
            "rm -f '$remoteZipPath'",
            "chown -R www-data:www-data . 2>/dev/null || true",
            "chmod -R 755 . 2>/dev/null || true"
        ];
        
        foreach ($commands as $cmd) {
            $sshCommand = "sshpass -p '{$this->config['password']}' ssh -t -p {$this->config['port']} -o StrictHostKeyChecking=no {$this->remoteUser}@{$this->remoteHost} '$cmd'";
            
            $output = shell_exec($sshCommand . ' 2>&1');
            
            // Check for specific errors
            if (str_contains($output ?? '', 'Permission denied') || str_contains($output ?? '', 'No such file')) {
                $this->error("❌ Command failed: $cmd");
                $this->error("   Error: " . trim($output));
                return false;
            }
        }
        
        return true;
    }

    private function clearRemoteCaches(): void
    {
        $this->info('🧹 Clearing remote caches...');
        
        $commands = [
            "cd /var/www/html/taxilanzhr/taxilanzhr && php artisan optimize:clear",
            "cd /var/www/html/taxilanzhr/taxilanzhr && php artisan view:clear",
            "cd /var/www/html/taxilanzhr/taxilanzhr && php artisan config:clear",
            "cd /var/www/html/taxilanzhr/taxilanzhr && php artisan route:clear",
        ];

        foreach ($commands as $command) {
            $sshCommand = "sshpass -p '{$this->config['password']}' ssh -t -p {$this->config['port']} -o StrictHostKeyChecking=no {$this->remoteUser}@{$this->remoteHost} '$command'";
            
            $output = shell_exec($sshCommand . ' 2>&1');
            
            if ($output === null || !str_contains($output, 'Error')) {
                $this->info('✅ Cache cleared');
            } else {
                $this->warn('⚠️  Cache clear warning');
            }
        }
    }

    private function download(string $profile): int
    {
        $this->warn('⚠️  Download functionality not implemented yet');
        $this->info('💡 Use upload for now, or implement download if needed');
        return 0;
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
