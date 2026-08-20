<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BackupFilamentPortal extends Command
{
    protected $signature = 'backup:filament-portal {--restore : Restaurar desde backup}';
    protected $description = 'Backup o restaurar archivos de Filament Portal (vistas, CSS, componentes)';

    private array $backupPaths = [
        // Vistas Livewire Portal
        'resources/views/livewire/portal-taxista-pro',
        
        // Componentes PHP
        'app/Livewire/PortalTaxistaPro.php',
        
        // CSS Themes
        'resources/css/filament/portal/theme.css',
        'resources/css/portal.css',
        'resources/css/portal-taxista.css',
        
        // Componentes Blade
        'resources/views/components/portal',
        
        // Pages Portal
        'resources/views/pages/portal',
        
        // Configuración Panel Provider
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
        $this->info('🔒 Creando backup de Filament Portal...');
        
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupDir = storage_path("app/backups/filament-portal/{$timestamp}");
        $zipPath = storage_path("app/backups/filament-portal_backup_{$timestamp}.zip");

        // Crear directorio de backup
        File::ensureDirectoryExists($backupDir);

        // Copiar archivos
        $copiedFiles = 0;
        foreach ($this->backupPaths as $path) {
            $fullPath = base_path($path);
            
            if (File::exists($fullPath)) {
                $targetPath = $backupDir . '/' . $path;
                $targetDir = dirname($targetPath);
                
                File::ensureDirectoryExists($targetDir);
                
                if (File::isDirectory($fullPath)) {
                    File::copyDirectory($fullPath, $targetPath);
                    $fileCount = count(File::allFiles($targetPath));
                    $this->line("✓ Directorio: {$path} ({$fileCount} archivos)");
                    $copiedFiles += $fileCount;
                } else {
                    File::copy($fullPath, $targetPath);
                    $this->line("✓ Archivo: {$path}");
                    $copiedFiles++;
                }
            } else {
                $this->warn("⚠ No encontrado: {$path}");
            }
        }

        // Crear ZIP
        $this->info('📦 Creando archivo ZIP...');
        $zip = new ZipArchive();
        
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $this->addFolderToZip($zip, $backupDir, '');
            $zip->close();
            
            // Limpiar directorio temporal
            File::deleteDirectory($backupDir);
            
            $zipSize = number_format(filesize($zipPath) / 1024 / 1024, 2);
            $this->info("✅ Backup completado!");
            $this->info("📁 Archivo: backups/filament-portal_backup_{$timestamp}.zip");
            $this->info("📊 Tamaño: {$zipSize} MB");
            $this->info("📄 Archivos: {$copiedFiles}");
            
            return 0;
        }

        $this->error('❌ Error creando el archivo ZIP');
        return 1;
    }

    private function restore(): int
    {
        $this->info('🔄 Buscando backups disponibles...');
        
        $backupDir = storage_path('app/backups/filament-portal');
        $backups = [];
        
        if (File::exists($backupDir)) {
            $files = File::glob($backupDir . '/filament-portal_backup_*.zip');
            
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
            $this->error('❌ No se encontraron backups');
            return 1;
        }

        // Mostrar lista de backups
        $this->info('📋 Backups disponibles:');
        foreach ($backups as $filename => $info) {
            $this->line("• {$filename} ({$info['size']} MB, {$info['timestamp']})");
        }

        // Seleccionar backup más reciente
        $latestBackup = array_key_first($backups);
        $selected = $this->ask('¿Qué backup deseas restaurar?', $latestBackup);

        if (!isset($backups[$selected])) {
            $this->error('❌ Backup no válido');
            return 1;
        }

        // Confirmar restauración
        if (!$this->confirm('⚠️  Esta acción sobreescribirá los archivos actuales. ¿Deseas continuar?')) {
            $this->info('❌ Operación cancelada');
            return 0;
        }

        // Extraer backup
        $this->info('📂 Extrayendo backup...');
        $tempDir = storage_path('app/backups/temp_restore_' . time());
        File::ensureDirectoryExists($tempDir);

        $zip = new ZipArchive();
        if ($zip->open($backups[$selected]['path']) === TRUE) {
            $zip->extractTo($tempDir);
            $zip->close();

            // Restaurar archivos
            $extractedDir = $tempDir . '/' . File::directories($tempDir)[0];
            $restoredFiles = 0;

            foreach ($this->backupPaths as $path) {
                $sourcePath = $extractedDir . '/' . $path;
                $targetPath = base_path($path);

                if (File::exists($sourcePath)) {
                    // Crear directorio destino si no existe
                    $targetDir = dirname($targetPath);
                    File::ensureDirectoryExists($targetDir);

                    if (File::isDirectory($sourcePath)) {
                        // Eliminar directorio existente
                        if (File::exists($targetPath)) {
                            File::deleteDirectory($targetPath);
                        }
                        File::copyDirectory($sourcePath, $targetPath);
                        $fileCount = count(File::allFiles($targetPath));
                        $this->line("✓ Directorio restaurado: {$path} ({$fileCount} archivos)");
                        $restoredFiles += $fileCount;
                    } else {
                        File::copy($sourcePath, $targetPath);
                        $this->line("✓ Archivo restaurado: {$path}");
                        $restoredFiles++;
                    }
                } else {
                    $this->warn("⚠ No encontrado en backup: {$path}");
                }
            }

            // Limpiar directorio temporal
            File::deleteDirectory($tempDir);

            // Limpiar cache
            $this->call('view:clear');
            $this->call('cache:clear');

            $this->info('✅ Restauración completada!');
            $this->info("📄 Archivos restaurados: {$restoredFiles}");
            $this->info('🧹 Cache limpiado');

            return 0;
        }

        $this->error('❌ Error extrayendo el backup');
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
