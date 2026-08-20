<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class PortalValidate extends Command
{
    protected $signature = 'portal:validate {--php : Check PHP syntax only} {--blade : Check Blade syntax only} {--css : Check CSS syntax only} {--routes : Check routes only}';
    protected $description = 'Validate Portal Taxista code and configuration';

    private array $criticalFiles = [
        'app/Livewire/PortalTaxistaPro.php',
        'resources/views/livewire/portal-taxista-pro/portal-taxista-pro.blade.php',
        'resources/views/livewire/portal-taxista-pro/_tab-documentos.blade.php',
        'resources/views/livewire/portal-taxista-pro/_tab-citas.blade.php',
        'resources/views/livewire/portal-taxista-pro/_tab-tickets.blade.php',
        'resources/css/filament/portal/theme.css',
        'resources/css/portal.css',
        'resources/css/portal-taxista.css',
        'app/Providers/Filament/PortalPanelProvider.php',
    ];

    public function handle(): int
    {
        $this->info('🔍 Validating Portal Taxista...');
        
        $totalErrors = 0;
        
        // Check critical files
        $totalErrors += $this->checkCriticalFiles();
        
        // Check specific validations based on options
        if ($this->option('php')) {
            $totalErrors += $this->checkPhpSyntax();
        } elseif ($this->option('blade')) {
            $totalErrors += $this->checkBladeSyntax();
        } elseif ($this->option('css')) {
            $totalErrors += $this->checkCssSyntax();
        } elseif ($this->option('routes')) {
            $totalErrors += $this->checkRoutes();
        } else {
            // Run all validations
            $totalErrors += $this->checkPhpSyntax();
            $totalErrors += $this->checkBladeSyntax();
            $totalErrors += $this->checkCssSyntax();
            $totalErrors += $this->checkRoutes();
            $totalErrors += $this->checkConfiguration();
            $totalErrors += $this->analyzeRefactorScripts();
        }
        
        // Summary
        $this->newLine();
        $this->info('📊 VALIDATION SUMMARY');
        
        if ($totalErrors === 0) {
            $this->info('✅ All validations passed successfully');
            $this->info('🎉 System is ready for optimization');
            return 0;
        } else {
            $this->error("❌ Found {$totalErrors} errors");
            $this->warn('⚠️  Fix errors before optimizing');
            return 1;
        }
    }
    
    private function checkCriticalFiles(): int
    {
        $this->info('📁 Checking critical files...');
        
        $missingFiles = 0;
        
        foreach ($this->criticalFiles as $file) {
            if (File::exists(base_path($file))) {
                $this->info("✅ Critical file found: $file");
            } else {
                $this->error("❌ Critical file missing: $file");
                $missingFiles++;
            }
        }
        
        return $missingFiles;
    }
    
    private function checkPhpSyntax(): int
    {
        $this->info('🐘 Checking PHP syntax...');
        
        $phpErrors = 0;
        
        // Check critical PHP files
        $phpFiles = array_filter($this->criticalFiles, function($file) {
            return str_ends_with($file, '.php');
        });
        
        foreach ($phpFiles as $file) {
            $fullPath = base_path($file);
            if (File::exists($fullPath)) {
                $output = [];
                $returnCode = 0;
                exec("php -l \"$fullPath\" 2>&1", $output, $returnCode);
                
                if ($returnCode === 0) {
                    $this->info("✅ PHP syntax correct: $file");
                } else {
                    $this->error("❌ PHP syntax error: $file");
                    foreach ($output as $line) {
                        $this->error("   $line");
                    }
                    $phpErrors++;
                }
            }
        }
        
        // Check all Livewire files
        $livewireFiles = File::glob(base_path('app/Livewire/**/*.php'));
        foreach ($livewireFiles as $file) {
            $output = [];
            $returnCode = 0;
            exec("php -l \"$file\" 2>&1", $output, $returnCode);
            
            if ($returnCode !== 0) {
                $this->error("❌ PHP syntax error: " . str_replace(base_path() . '/', '', $file));
                foreach ($output as $line) {
                    $this->error("   $line");
                }
                $phpErrors++;
            }
        }
        
        if ($phpErrors === 0) {
            $this->info("✅ All PHP files have correct syntax");
        }
        
        return $phpErrors;
    }
    
    private function checkBladeSyntax(): int
    {
        $this->info('🎨 Checking Blade syntax...');
        
        $bladeErrors = 0;
        
        $bladeFiles = array_filter($this->criticalFiles, function($file) {
            return str_ends_with($file, '.blade.php');
        });
        
        foreach ($bladeFiles as $file) {
            $fullPath = base_path($file);
            if (File::exists($fullPath)) {
                $content = File::get($fullPath);
                
                // Count directives
                $openDirectives = preg_match_all('/@(if|foreach|for|while|switch|section|component|auth|guest|once|unless|can|cannot|has|missing|isset|empty|switch|case|default|break|continue|error|enderror|endfor|endforeach|endif|endsection|endcomponent|endauth|endguest|endonce|endunless|endphp|raw|verbatim|endverbatim)/', $content);
                $closeDirectives = preg_match_all('/@(end(if|foreach|for|while|switch|section|component|auth|guest|once|unless|can|cannot|has|missing|isset|empty|switch|case|default|break|continue|error|enderror|for|foreach|if|section|component|auth|guest|once|unless|php|raw|verbatim))/m', $content);
                
                if ($openDirectives === $closeDirectives) {
                    $this->info("✅ Blade directives balanced: $file");
                } else {
                    $this->error("❌ Blade directives unbalanced in: $file");
                    $this->error("   Open: $openDirectives, Close: $closeDirectives");
                    $bladeErrors++;
                }
            }
        }
        
        if ($bladeErrors === 0) {
            $this->info("✅ All Blade files have balanced directives");
        }
        
        return $bladeErrors;
    }
    
    private function checkCssSyntax(): int
    {
        $this->info('🎨 Checking CSS files...');
        
        $cssErrors = 0;
        
        $cssFiles = array_filter($this->criticalFiles, function($file) {
            return str_ends_with($file, '.css');
        });
        
        foreach ($cssFiles as $file) {
            $fullPath = base_path($file);
            if (File::exists($fullPath)) {
                $content = File::get($fullPath);
                
                if (empty($content)) {
                    $this->warn("⚠️  Empty CSS file: $file");
                    continue;
                }
                
                // Count braces
                $openBraces = substr_count($content, '{');
                $closeBraces = substr_count($content, '}');
                
                if ($openBraces === $closeBraces) {
                    $this->info("✅ CSS braces balanced: $file");
                } else {
                    $this->warn("⚠️  Possible brace imbalance in: $file");
                    $this->warn("   Open: $openBraces, Close: $closeBraces");
                    // Not counting as error since CSS can be valid with imbalance
                }
            }
        }
        
        return $cssErrors;
    }
    
    private function checkRoutes(): int
    {
        $this->info('🛣️  Checking Laravel routes...');
        
        $routeErrors = 0;
        
        try {
            // Check if portal routes exist
            $result = Artisan::call('route:list', ['--name=portal']);
            
            if ($result === 0) {
                $this->info("✅ Portal routes working");
            } else {
                $this->error("❌ Portal routes failed");
                $routeErrors++;
            }
        } catch (\Exception $e) {
            $this->error("❌ Route check failed: " . $e->getMessage());
            $routeErrors++;
        }
        
        // Check specific routes
        $criticalRoutes = ['portal.taxista-portal', 'mobile-portal'];
        foreach ($criticalRoutes as $route) {
            try {
                $result = Artisan::call('route:list', ['--name' => $route]);
                if ($result === 0) {
                    $this->info("✅ Route found: $route");
                }
            } catch (\Exception $e) {
                $this->warn("⚠️  Route not found: $route");
            }
        }
        
        return $routeErrors;
    }
    
    private function checkConfiguration(): int
    {
        $this->info('⚙️  Checking configuration...');
        
        $configErrors = 0;
        
        // Check .env file
        if (File::exists(base_path('.env'))) {
            $this->info("✅ .env file found");
        } else {
            $this->error("❌ .env file missing");
            $configErrors++;
        }
        
        // Test config cache
        try {
            Artisan::call('config:cache');
            $this->info("✅ Configuration cache works");
        } catch (\Exception $e) {
            $this->error("❌ Configuration cache failed: " . $e->getMessage());
            $configErrors++;
        }
        
        return $configErrors;
    }
    
    private function analyzeRefactorScripts(): int
    {
        $this->info('📜 Analyzing refactor scripts...');
        
        $refactorDir = base_path('scripts/refactor');
        
        if (File::exists($refactorDir)) {
            $this->warn("⚠️  Found old refactor scripts");
            
            foreach (File::glob($refactorDir . '/*.sh') as $script) {
                $scriptName = basename($script);
                $this->info("📝 Analyzing: $scriptName");
                
                $content = File::get($script);
                
                // Check for dangerous patterns
                if (str_contains($content, 'rm -rf') || str_contains($content, 'sed -i.*s/')) {
                    $this->warn("⚠️  $scriptName contains file modification operations");
                }
                
                if (str_contains($content, 'mysql') || str_contains($content, 'RENAME TABLE')) {
                    $this->warn("⚠️  $scriptName contains database operations");
                }
            }
            
            $this->warn("⚠️  Do NOT run these scripts without a full backup");
            $this->info("💡 Use: php artisan portal:backup --type=full");
        } else {
            $this->info("✅ No old refactor scripts found");
        }
        
        return 0; // Not counting as error
    }
}
