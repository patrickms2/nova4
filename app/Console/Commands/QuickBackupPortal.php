<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class QuickBackupPortal extends Command
{
    protected $signature = 'backup:portal-quick {--restore : Restaurar último backup}';
    protected $description = 'Backup rápido del portal actual (solo archivos modificados)';

    private array $criticalFiles = [
        'app/Livewire/PortalTaxistaPro.php',
        'resources/views/livewire/portal-taxista-pro/portal-taxista-pro.blade.php',
        'resources/views/livewire/portal-taxista-pro/_tab-documentos.blade.php',
        'resources/views/livewire/portal-taxista-pro/_tab-citas.blade.php',
        'resources/views/livewire/portal-taxista-pro/_tab-tickets.blade.php',
        'resources/css/filament/portal/theme.css',
        'resources/css/portal.css',
        'resources/css/portal-taxista.css',
    ];

    public function handle(): int
    {
        if ($this->option('restore')) {
            return $this->restoreQuick();
        }

        return $this->backupQuick();
    }

    private function backupQuick(): int
    {
        $this->info('⚡ Creando backup rápido del Portal...');
        
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupDir = storage_path("app/backups/portal-quick/{$timestamp}");

        File::ensureDirectoryExists($backupDir);

        $copiedFiles = 0;
        foreach ($this->criticalFiles as $file) {
            $sourcePath = base_path($file);
            $targetPath = $backupDir . '/' . basename($file);

            if (File::exists($sourcePath)) {
                File::copy($sourcePath, $targetPath);
                $this->line("✓ " . basename($file));
                $copiedFiles++;
            } else {
                $this->warn("⚠ No encontrado: {$file}");
            }
        }

        // Guardar timestamp del último backup
        File::put(storage_path('app/backups/portal-quick/latest.txt'), $timestamp);

        $this->info("✅ Backup rápido completado!");
        $this->info("📁 Directorio: backups/portal-quick/{$timestamp}");
        $this->info("📄 Archivos: {$copiedFiles}");

        return 0;
    }

    private function restoreQuick(): int
    {
        $this->info('🔄 Restaurando último backup rápido...');

        $latestFile = storage_path('app/backups/portal-quick/latest.txt');
        
        if (!File::exists($latestFile)) {
            $this->error('❌ No se encontró ningún backup rápido');
            return 1;
        }

        $timestamp = trim(File::get($latestFile));
        $backupDir = storage_path("app/backups/portal-quick/{$timestamp}");

        if (!File::exists($backupDir)) {
            $this->error('❌ El directorio de backup no existe');
            return 1;
        }

        if (!$this->confirm('⚠️  Esto restaurará los archivos críticos del portal. ¿Continuar?')) {
            $this->info('❌ Operación cancelada');
            return 0;
        }

        $restoredFiles = 0;
        foreach ($this->criticalFiles as $file) {
            $backupFile = $backupDir . '/' . basename($file);
            $targetPath = base_path($file);

            if (File::exists($backupFile)) {
                File::copy($backupFile, $targetPath);
                $this->line("✓ " . basename($file));
                $restoredFiles++;
            } else {
                $this->warn("⚠ No encontrado en backup: " . basename($file));
            }
        }

        // Limpiar cache
        $this->call('view:clear');

        $this->info("✅ Restauración rápida completada!");
        $this->info("📄 Archivos restaurados: {$restoredFiles}");
        $this->info("🧹 Vista cache limpiada");

        return 0;
    }
}
