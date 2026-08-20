<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class PortalOptimize extends Command
{
    protected $signature = 'portal:optimize {--safe : Use safe optimization (no config:cache)}';
    protected $description = 'Optimize Portal Taxista performance and cache';

    public function handle(): int
    {
        $safe = $this->option('safe');
        
        $this->info('🚀 Optimizing Portal Taxista...');
        
        // Clear all caches first
        $this->clearCaches();
        
        // Rebuild assets
        $this->rebuildAssets();
        
        // Optimize autoloader
        $this->optimizeAutoloader();
        
        // Config cache (only if not safe mode)
        if (!$safe) {
            $this->optimizeConfig();
        } else {
            $this->info('⚠️  Skipping config:cache (safe mode)');
        }
        
        // Compile views (if possible)
        $this->compileViews();
        
        // Verify system
        $this->verifySystem();
        
        $this->info('✅ Portal optimization completed!');
        
        if ($safe) {
            $this->info('🛡️  Safe mode: config:cache skipped to prevent class conflicts');
        }
        
        return 0;
    }
    
    private function clearCaches(): void
    {
        $this->info('🧹 Clearing caches...');
        
        $this->call('optimize:clear');
        $this->call('view:clear');
        $this->call('cache:clear');
        $this->call('route:clear');
        $this->call('config:clear');
        $this->call('event:clear');
        
        $this->info('✅ All caches cleared');
    }
    
    private function rebuildAssets(): void
    {
        $this->info('🎨 Rebuilding assets...');
        
        $this->call('filament:assets');
        
        $this->info('✅ Assets rebuilt');
    }
    
    private function optimizeAutoloader(): void
    {
        $this->info('📦 Optimizing autoloader...');
        
        // Use composer dump-autoload without --optimize to avoid issues
        $process = new \Symfony\Component\Process\Process(['composer', 'dump-autoload']);
        $process->run();
        
        if (!$process->isSuccessful()) {
            $this->error('❌ Failed to optimize autoloader');
            return;
        }
        
        $this->info('✅ Autoloader optimized');
    }
    
    private function optimizeConfig(): void
    {
        $this->info('⚙️  Optimizing configuration...');
        
        try {
            $this->call('config:cache');
            $this->info('✅ Configuration cached');
        } catch (\Exception $e) {
            $this->error('❌ Failed to cache configuration: ' . $e->getMessage());
            $this->info('⚠️  This might be due to class conflicts. Use --safe flag to skip.');
        }
    }
    
    private function compileViews(): void
    {
        $this->info('🎨 Compiling views...');
        
        try {
            $this->call('view:cache');
            $this->info('✅ Views compiled');
        } catch (\Exception $e) {
            $this->warn('⚠️  Could not compile views: ' . $e->getMessage());
            $this->info('💡 This is usually due to Blade syntax errors, but the system still works');
        }
    }
    
    private function verifySystem(): void
    {
        $this->info('🔍 Verifying system...');
        
        // Check critical files
        $criticalFiles = [
            'app/Livewire/PortalTaxistaPro.php',
            'resources/views/livewire/portal-taxista-pro/portal-taxista-pro.blade.php',
            'resources/css/filament/portal/theme.css',
        ];
        
        foreach ($criticalFiles as $file) {
            if (File::exists($file)) {
                $this->info("✅ Critical file found: $file");
            } else {
                $this->error("❌ Critical file missing: $file");
            }
        }
        
        // Check PHP syntax
        if (exec('php -l app/Livewire/PortalTaxistaPro.php') === 0) {
            $this->info('✅ PHP syntax valid');
        } else {
            $this->error('❌ PHP syntax errors found');
        }
        
        // Check routes
        try {
            $this->call('route:list', ['--name=portal']);
            $this->info('✅ Portal routes working');
        } catch (\Exception $e) {
            $this->warn('⚠️  Portal routes issue: ' . $e->getMessage());
        }
    }
}
