<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PortalSyncSimple extends Command
{
    protected $signature = 'portal:sync-simple {--upload : Upload to server} {--download : Download from server}';
    protected $description = 'Simple sync for Portal Taxista using scp (safer alternative)';

    private array $config;
    private string $remoteHost;
    private string $remoteUser;
    private string $remotePath;
    private string $localPath;

    private array $syncFiles = [
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
        $this->loadConfig();
        
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

    private function upload(): int
    {
        $this->info('📤 Uploading Portal Taxista files (simple sync)...');

        $uploadedFiles = 0;
        $failedFiles = 0;

        foreach ($this->syncFiles as $file) {
            $localFile = $this->localPath . '/' . $file;
            $remoteFile = $this->remoteUser . '@' . $this->remoteHost . ':' . $this->remotePath . '/' . $file;

            if (!File::exists($localFile)) {
                $this->warn("⚠️  Local file not found: $file");
                $failedFiles++;
                continue;
            }

            $this->info("📤 Uploading: $file");

            // Create remote directory if needed
            $remoteDir = dirname($this->remotePath . '/' . $file);
            $mkdirCommand = "sshpass -p '{$this->config['password']}' ssh -t -p {$this->config['port']} -o StrictHostKeyChecking=no {$this->remoteUser}@{$this->remoteHost} 'cd /var/www/html/taxilanzhr/taxilanzhr && mkdir -p \"$remoteDir\"'";
            shell_exec($mkdirCommand);

            // Upload file using scp
            $scpCommand = "sshpass -p '{$this->config['password']}' scp -P {$this->config['port']} -o StrictHostKeyChecking=no \"$localFile\" \"$remoteFile\"";
            
            $output = shell_exec($scpCommand . ' 2>&1');
            
            if ($output === null || str_contains($output ?? '', '100%')) {
                $this->info("✅ Uploaded: $file");
                $uploadedFiles++;
            } else {
                $this->error("❌ Failed to upload: $file");
                if ($output) {
                    $this->error("   Error: " . trim($output));
                }
                $failedFiles++;
            }
        }

        $this->info("📊 Upload Summary:");
        $this->info("   ✅ Uploaded: $uploadedFiles files");
        $this->info("   ❌ Failed: $failedFiles files");

        if ($failedFiles === 0) {
            $this->info("🎉 All files uploaded successfully!");
            $this->clearRemoteCaches();
            return 0;
        } else {
            $this->error("❌ Some files failed to upload");
            return 1;
        }
    }

    private function download(): int
    {
        $this->info('📥 Downloading Portal Taxista files (simple sync)...');

        // Create backup before download
        if (!$this->confirm('⚠️  This will overwrite local files. Continue?')) {
            $this->info('❌ Operation cancelled');
            return 0;
        }

        $downloadedFiles = 0;
        $failedFiles = 0;

        foreach ($this->syncFiles as $file) {
            $localFile = $this->localPath . '/' . $file;
            $remoteFile = $this->remoteUser . '@' . $this->remoteHost . ':' . $this->remotePath . '/' . $file;

            $this->info("📥 Downloading: $file");

            // Create local directory if needed
            $localDir = dirname($localFile);
            if (!File::exists($localDir)) {
                File::ensureDirectoryExists($localDir);
            }

            // Download file using scp
            $scpCommand = "sshpass -p '{$this->config['password']}' scp -P {$this->config['port']} -o StrictHostKeyChecking=no \"$remoteFile\" \"$localFile\"";
            
            $output = shell_exec($scpCommand . ' 2>&1');
            
            if ($output === null || str_contains($output ?? '', '100%')) {
                $this->info("✅ Downloaded: $file");
                $downloadedFiles++;
            } else {
                $this->error("❌ Failed to download: $file");
                if ($output) {
                    $this->error("   Error: " . trim($output));
                }
                $failedFiles++;
            }
        }

        $this->info("📊 Download Summary:");
        $this->info("   ✅ Downloaded: $downloadedFiles files");
        $this->info("   ❌ Failed: $failedFiles files");

        if ($failedFiles === 0) {
            $this->info("🎉 All files downloaded successfully!");
            
            // Clear local caches
            $this->call('optimize:clear');
            
            return 0;
        } else {
            $this->error("❌ Some files failed to download");
            return 1;
        }
    }

    private function status(): int
    {
        $this->info('📊 Checking simple sync status...');

        // Test SSH connection
        if (!$this->testSshConnection()) {
            $this->error('❌ Cannot connect to remote server');
            return 1;
        }

        // Check files
        $this->info('🔍 Checking files...');
        
        foreach ($this->syncFiles as $file) {
            $localFile = $this->localPath . '/' . $file;
            $remoteFile = $this->remotePath . '/' . $file;

            $localExists = File::exists($localFile);
            $remoteExists = $this->remoteFileExists($remoteFile);

            if ($localExists && $remoteExists) {
                $localSize = filesize($localFile);
                $remoteSize = $this->getRemoteFileSize($remoteFile);
                
                $status = $localSize === $remoteSize ? '✅' : '⚠️';
                $this->line("$status $file (Local: {$localSize}b, Remote: {$remoteSize}b)");
            } elseif ($localExists) {
                $this->line("📤 $file (Local only)");
            } elseif ($remoteExists) {
                $this->line("📥 $file (Remote only)");
            } else {
                $this->line("❌ $file (Missing)");
            }
        }

        return 0;
    }

    private function testSshConnection(): bool
    {
        $this->info('🔌 Testing SSH connection...');
        
        $command = "sshpass -p '{$this->config['password']}' ssh -t -p {$this->config['port']} -o StrictHostKeyChecking=no {$this->remoteUser}@{$this->remoteHost} 'cd /var/www/html/ && echo connection_ok'";
        
        $output = shell_exec($command . ' 2>&1');
        
        return str_contains($output ?? '', 'connection_ok');
    }

    private function remoteFileExists(string $remoteFile): bool
    {
        $command = "sshpass -p '{$this->config['password']}' ssh -t -p {$this->config['port']} -o StrictHostKeyChecking=no {$this->remoteUser}@{$this->remoteHost} 'cd /var/www/html/taxilanzhr/taxilanzhr && test -f \"$remoteFile\" && echo exists'";
        
        $output = shell_exec($command . ' 2>&1');
        
        return str_contains($output ?? '', 'exists');
    }

    private function getRemoteFileSize(string $remoteFile): int
    {
        $command = "sshpass -p '{$this->config['password']}' ssh -t -p {$this->config['port']} -o StrictHostKeyChecking=no {$this->remoteUser}@{$this->remoteHost} 'cd /var/www/html/taxilanzhr/taxilanzhr && stat -c%s \"$remoteFile\" 2>/dev/null'";
        
        $output = shell_exec($command . ' 2>&1');
        
        // Extract size from output, handle pseudo-terminal warning
        $lines = explode("\n", trim($output ?? '0'));
        foreach ($lines as $line) {
            if (is_numeric(trim($line))) {
                return (int)trim($line);
            }
        }
        
        return 0;
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
