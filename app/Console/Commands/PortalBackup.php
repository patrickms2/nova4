<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class PortalBackup extends Command
{
    protected $signature = 'portal:backup {--restore : Restore from backup} {--type=quick : Backup type (quick|full)}';
    protected $description = 'Backup or restore Portal Taxista files';

    private array $quickFiles = [
        'app/Livewire/PortalTaxistaPro.php',
        'resources/views/livewire/portal-taxista-pro/portal-taxista-pro.blade.php',
        'resources/views/livewire/portal-taxista-pro/_tab-documentos.blade.php',
        'resources/views/livewire/portal-taxista-pro/_tab-citas.blade.php',
        'resources/views/livewire/portal-taxista-pro/_tab-tickets.blade.php',
        'resources/css/filament/portal/theme.css',
        'resources/css/portal.css',
        'resources/css/portal-taxista.css',
    ];

    private array $fullPaths = [
        'resources/views/livewire/portal-taxista-pro',
        'app/Livewire/PortalTaxistaPro.php',
        'resources/css/filament/portal/theme.css',
        'resources/css/portal.css',
        'resources/css/portal-taxista.css',
        'resources/views/components/portal',
        'app/Providers/Filament/PortalPanelProvider.php',
    ];

    public function handle(): int
    {
        if ($this->option('restore')) {
            return $this->restore();
        }

        return $this->backup();
    }

    private function backup(): int
    {
        $type = $this->option('type');
        
        if ($type === 'full') {
            return $this->fullBackup();
        }

        return $this->quickBackup();
    }

    private function quickBackup(): int
    {
        $this->info('⚡ Creating quick backup...');
        
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupDir = storage_path("app/backups/portal-quick/{$timestamp}");
        
        File::ensureDirectoryExists($backupDir);

        $copiedFiles = 0;
        foreach ($this->quickFiles as $file) {
            $sourcePath = base_path($file);
            $targetPath = $backupDir . '/' . basename($file);

            if (File::exists($sourcePath)) {
                File::copy($sourcePath, $targetPath);
                $this->line("✓ " . basename($file));
                $copiedFiles++;
            } else {
                $this->warn("⚠ Not found: $file");
            }
        }

        // Save latest backup timestamp
        File::put(storage_path('app/backups/portal-quick/latest.txt'), $timestamp);

        $this->info("✅ Quick backup completed!");
        $this->info("📁 Directory: backups/portal-quick/{$timestamp}");
        $this->info("📄 Files: {$copiedFiles}");

        return 0;
    }

    private function fullBackup(): int
    {
        $this->info('🔒 Creating full backup...');
        
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupDir = storage_path("app/backups/portal-full/{$timestamp}");
        $zipPath = storage_path("app/backups/portal-full_backup_{$timestamp}.zip");

        File::ensureDirectoryExists($backupDir);

        $copiedFiles = 0;
        foreach ($this->fullPaths as $path) {
            $fullPath = base_path($path);
            
            if (File::exists($fullPath)) {
                $targetPath = $backupDir . '/' . $path;
                $targetDir = dirname($targetPath);
                
                File::ensureDirectoryExists($targetDir);
                
                if (File::isDirectory($fullPath)) {
                    File::copyDirectory($fullPath, $targetPath);
                    $fileCount = count(File::allFiles($targetPath));
                    $this->line("✓ Directory: $path ({$fileCount} files)");
                    $copiedFiles += $fileCount;
                } else {
                    File::copy($fullPath, $targetPath);
                    $this->line("✓ File: $path");
                    $copiedFiles++;
                }
            } else {
                $this->warn("⚠ Not found: $path");
            }
        }

        // Create ZIP
        $this->info('📦 Creating ZIP file...');
        $zip = new ZipArchive();
        
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $this->addFolderToZip($zip, $backupDir, '');
            $zip->close();
            
            // Clean up temp directory
            File::deleteDirectory($backupDir);
            
            $zipSize = number_format(filesize($zipPath) / 1024 / 1024, 2);
            $this->info("✅ Full backup completed!");
            $this->info("📁 File: backups/portal-full_backup_{$timestamp}.zip");
            $this->info("📊 Size: {$zipSize} MB");
            $this->info("📄 Files: {$copiedFiles}");
            
            return 0;
        }

        $this->error('❌ Error creating ZIP file');
        return 1;
    }

    private function restore(): int
    {
        $this->info('🔄 Restoring from backup...');
        
        $type = $this->option('type');
        
        if ($type === 'full') {
            return $this->restoreFull();
        }

        return $this->restoreQuick();
    }

    private function restoreQuick(): int
    {
        $latestFile = storage_path('app/backups/portal-quick/latest.txt');
        
        if (!File::exists($latestFile)) {
            $this->error('❌ No quick backup found');
            return 1;
        }

        $timestamp = trim(File::get($latestFile));
        $backupDir = storage_path("app/backups/portal-quick/{$timestamp}");

        if (!File::exists($backupDir)) {
            $this->error('❌ Backup directory not found');
            return 1;
        }

        if (!$this->confirm('⚠️  This will overwrite current files. Continue?')) {
            $this->info('❌ Operation cancelled');
            return 0;
        }

        $restoredFiles = 0;
        foreach ($this->quickFiles as $file) {
            $backupFile = $backupDir . '/' . basename($file);
            $targetPath = base_path($file);

            if (File::exists($backupFile)) {
                File::copy($backupFile, $targetPath);
                $this->line("✓ " . basename($file));
                $restoredFiles++;
            } else {
                $this->warn("⚠ Not found in backup: " . basename($file));
            }
        }

        // Clear view cache
        $this->call('view:clear');

        $this->info("✅ Quick restore completed!");
        $this->info("📄 Files restored: {$restoredFiles}");

        return 0;
    }

    private function restoreFull(): int
    {
        $this->info('🔄 Restoring from full backup...');
        
        // List available backups
        $backupDir = storage_path('app/backups/portal-full');
        $backups = [];
        
        if (File::exists($backupDir)) {
            $files = File::glob($backupDir . '/portal-full_backup_*.zip');
            
            foreach ($files as $file) {
                $filename = basename($file);
                $timestamp = preg_match('/(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})/', $filename, $matches) 
                    ? $matches[1] 
                    : 'unknown';
                
                $backups[$filename] = [
                    'path' => $file,
                    'size' => number_format(filesize($file) / 1024 / 1024, 2),
                    'timestamp' => $timestamp,
                ];
            }
        }

        if (empty($backups)) {
            $this->error('❌ No full backups found');
            return 1;
        }

        // Show available backups
        $this->info('📋 Available full backups:');
        foreach ($backups as $filename => $info) {
            $this->line("• {$filename} ({$info['size']} MB, {$info['timestamp']})");
        }

        // Select backup
        $latestBackup = array_key_first($backups);
        $selected = $this->ask('Which backup to restore?', $latestBackup);

        if (!isset($backups[$selected])) {
            $this->error('❌ Invalid backup selection');
            return 1;
        }

        if (!$this->confirm('⚠️  This will overwrite current files. Continue?')) {
            $this->info('❌ Operation cancelled');
            return 0;
        }

        // Extract and restore
        $this->info('📂 Extracting backup...');
        $tempDir = storage_path('app/backups/temp_restore_' . time());
        File::ensureDirectoryExists($tempDir);

        $zip = new ZipArchive();
        if ($zip->open($backups[$selected]['path']) === TRUE) {
            $zip->extractTo($tempDir);
            $zip->close();

            // Restore files
            $extractedDir = $tempDir . '/' . File::directories($tempDir)[0];
            $restoredFiles = 0;

            foreach ($this->fullPaths as $path) {
                $sourcePath = $extractedDir . '/' . $path;
                $targetPath = base_path($path);

                if (File::exists($sourcePath)) {
                    $targetDir = dirname($targetPath);
                    File::ensureDirectoryExists($targetDir);

                    if (File::isDirectory($sourcePath)) {
                        if (File::exists($targetPath)) {
                            File::deleteDirectory($targetPath);
                        }
                        File::copyDirectory($sourcePath, $targetPath);
                        $fileCount = count(File::allFiles($targetPath));
                        $this->line("✓ Directory restored: $path ({$fileCount} files)");
                        $restoredFiles += $fileCount;
                    } else {
                        File::copy($sourcePath, $targetPath);
                        $this->line("✓ File restored: $path");
                        $restoredFiles++;
                    }
                }
            }

            // Clean up
            File::deleteDirectory($tempDir);

            // Clear caches
            $this->call('optimize:clear');

            $this->info("✅ Full restore completed!");
            $this->info("📄 Files restored: {$restoredFiles}");

            return 0;
        }

        $this->error('❌ Error extracting backup');
        return 1;
    }

    private function addFolderToZip(ZipArchive $zip, string $folder, string $relativePath): void
    {
        $files = File::allFiles($folder);
        
        foreach ($files as $file) {
            $filePath = $file->getPathname();
            $relativeFilePath = $relativePath . '/' . $file->getRelativePathname();
            
            $zip->addFile($filePath, ltrim($relativeFilePath, '/'));
        }
    }
}
