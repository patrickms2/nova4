<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PortalSync extends Command
{
    protected $signature = 'portal:sync {--upload : Upload to server} {--download : Download from server}';
    protected $description = 'Sync Portal Taxista with remote server via SSH';

    private array $config;
    private string $remoteHost;
    private string $remoteUser;
    private string $remotePath;
    private string $localPath;

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
            'exclude' => [
                'node_modules',
                'vendor',
                'storage/logs',
                'storage/framework/cache',
                'storage/framework/sessions',
                'storage/framework/views',
                '.git',
                '.DS_Store',
                '*.log',
                'bootstrap/cache',
            ],
        ];

        $this->remoteHost = $this->config['host'];
        $this->remoteUser = $this->config['user'];
        $this->remotePath = $this->config['remote_path'];
        $this->localPath = $this->config['local_path'];

        // Validate config
        if (!$this->remoteHost || !$this->remoteUser) {
            $this->error('❌ SSH configuration missing in .env file');
            $this->error('Add these lines to your .env:');
            $this->error('SYNC_SSH_HOST=your-server.com');
            $this->error('SYNC_SSH_USER=your-username');
            $this->error('SYNC_SSH_PASSWORD=your-password');
            $this->error('SYNC_SSH_PORT=22');
            $this->error('SYNC_REMOTE_PATH=/var/www/nova');
            return;
        }

        $this->info("🔗 SSH Config: {$this->remoteUser}@{$this->remoteHost}:{$this->config['port']}");
        $this->info("📁 Remote: {$this->remotePath}");
        $this->info("📁 Local: {$this->localPath}");
    }

    private function upload(): int
    {
        $this->info('📤 Uploading Portal Taxista to server...');

        // Create exclude file for rsync
        $excludeFile = tempnam(sys_get_temp_dir(), 'rsync_exclude');
        foreach ($this->config['exclude'] as $exclude) {
            file_put_contents($excludeFile, $exclude . PHP_EOL, FILE_APPEND);
        }

        // Build rsync command
        $rsyncCommand = $this->buildRsyncCommand($excludeFile, 'upload');

        $this->info('🔄 Starting upload...');
        $this->line("Command: {$rsyncCommand}");

        // Execute rsync
        // Use shell_exec for better handling of complex commands
        $output = shell_exec($rsyncCommand . ' 2>&1');
        
        if ($output !== null) {
            $this->line($output);
        }
        
        // Check if rsync succeeded (rsync returns 0 on success)
        $exitCode = shell_exec('echo $?');
        
        // Clean up
        unlink($excludeFile);

        if (trim($exitCode) === '0') {
            $this->info('✅ Upload completed successfully');
            
            // Clear remote caches
            $this->clearRemoteCaches();
            
            return 0;
        } else {
            $this->error('❌ Upload failed');
            return 1;
        }
    }

    private function download(): int
    {
        $this->info('📥 Downloading Portal Taxista from server...');

        // Create backup before download
        if (!$this->confirm('⚠️  This will overwrite local files. Create backup first?', true)) {
            $this->call('portal:backup', ['--type' => 'quick']);
        }

        // Create exclude file for rsync
        $excludeFile = tempnam(sys_get_temp_dir(), 'rsync_exclude');
        foreach ($this->config['exclude'] as $exclude) {
            file_put_contents($excludeFile, $exclude . PHP_EOL, FILE_APPEND);
        }

        // Build rsync command
        $rsyncCommand = $this->buildRsyncCommand($excludeFile, 'download');

        $this->info('🔄 Starting download...');
        $this->line("Command: {$rsyncCommand}");

        // Execute rsync
        $process = new \Symfony\Component\Process\Process(explode(' ', $rsyncCommand));
        $process->setTimeout(300);

        $process->start();

        foreach ($process as $type => $data) {
            if ($type === \Symfony\Component\Process\Process::OUT) {
                $this->line($data);
            } else {
                $this->error($data);
            }
        }

        // Clean up
        unlink($excludeFile);

        if ($process->isSuccessful()) {
            $this->info('✅ Download completed successfully');
            
            // Clear local caches
            $this->call('optimize:clear');
            
            return 0;
        } else {
            $this->error('❌ Download failed');
            return 1;
        }
    }

    private function status(): int
    {
        $this->info('📊 Checking sync status...');

        // Test SSH connection
        if (!$this->testSshConnection()) {
            $this->error('❌ Cannot connect to remote server');
            return 1;
        }

        // Check remote files
        $this->info('🔍 Checking remote files...');
        $remoteFiles = $this->getRemoteFiles();

        // Check local files
        $this->info('🔍 Checking local files...');
        $localFiles = $this->getLocalFiles();

        // Compare
        $this->newLine();
        $this->info('📋 SYNC STATUS');
        $this->info("Remote files: " . count($remoteFiles));
        $this->info("Local files: " . count($localFiles));

        // Show differences
        $differences = array_merge(
            array_diff($remoteFiles, $localFiles),
            array_diff($localFiles, $remoteFiles)
        );

        if (!empty($differences)) {
            $this->warn('⚠️  Files that differ:');
            foreach ($differences as $file) {
                $this->line("  • $file");
            }
        } else {
            $this->info('✅ Files are in sync');
        }

        return 0;
    }

    private function buildRsyncCommand(string $excludeFile, string $direction): string
    {
        $excludeOption = "--exclude-from={$excludeFile}";
        
        // Use sshpass if password is configured
        $rsyncPrefix = "";
        $sshOptions = "-e \"ssh -p {$this->config['port']} -o StrictHostKeyChecking=no\"";
        
        if ($this->config['password']) {
            if (shell_exec('which sshpass') !== null) {
                $rsyncPrefix = "sshpass -p '{$this->config['password']}' ";
            } else {
                $this->warn('⚠️  sshpass not found. Please install it: brew install hudochenkov/sshpass/sshpass');
                $sshOptions = "-e \"ssh -p {$this->config['port']} -o StrictHostKeyChecking=no -o PasswordAuthentication=yes\"";
            }
        }
        
        $rsyncOptions = "-avz --progress --delete {$excludeOption} {$sshOptions}";

        if ($direction === 'upload') {
            return "{$rsyncPrefix}rsync {$rsyncOptions} {$this->localPath}/ {$this->remoteUser}@{$this->remoteHost}:{$this->remotePath}/";
        } else {
            return "{$rsyncPrefix}rsync {$rsyncOptions} {$this->remoteUser}@{$this->remoteHost}:{$this->remotePath}/ {$this->localPath}/";
        }
    }

    private function testSshConnection(): bool
    {
        $this->info('🔌 Testing SSH connection...');
        
        $command = "ssh -o BatchMode=yes -o ConnectTimeout=10 -p {$this->config['port']} {$this->remoteUser}@{$this->remoteHost} 'echo connection_ok'";
        
        $process = new \Symfony\Component\Process\Process(explode(' ', $command));
        $process->setTimeout(10);
        $process->run();

        return $process->isSuccessful() && trim($process->getOutput()) === 'connection_ok';
    }

    private function getRemoteFiles(): array
    {
        $command = "ssh -p {$this->config['port']} {$this->remoteUser}@{$this->remoteHost} 'find {$this->remotePath} -name \"*.php\" -o -name \"*.blade.php\" -o -name \"*.css\" 2>/dev/null'";
        
        $process = new \Symfony\Component\Process\Process(explode(' ', $command));
        $process->setTimeout(30);
        $process->run();

        if ($process->isSuccessful()) {
            $files = explode("\n", trim($process->getOutput()));
            return array_filter($files, function($file) {
                return !empty($file);
            });
        }

        return [];
    }

    private function getLocalFiles(): array
    {
        $files = [];
        
        // PHP files
        $phpFiles = File::glob($this->localPath . '/app/Livewire/**/*.php');
        $files = array_merge($files, $phpFiles);
        
        // Blade files
        $bladeFiles = File::glob($this->localPath . '/resources/views/livewire/portal-taxista-pro/**/*.blade.php');
        $files = array_merge($files, $bladeFiles);
        
        // CSS files
        $cssFiles = File::glob($this->localPath . '/resources/css/**/*.css');
        $files = array_merge($files, $cssFiles);
        
        return array_map(function($file) use ($localPath) {
            return str_replace($this->localPath . '/', '', $file);
        }, $files);
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
            $sshCommand = "ssh -p {$this->config['port']} {$this->remoteUser}@{$this->remoteHost} '{$command}'";
            
            $process = new \Symfony\Component\Process\Process(explode(' ', $sshCommand));
            $process->setTimeout(30);
            $process->run();

            if ($process->isSuccessful()) {
                $this->info('✅ Remote cache cleared');
            } else {
                $this->warn('⚠️  Could not clear remote cache');
            }
        }
    }
}
