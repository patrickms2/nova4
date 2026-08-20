<?php

namespace App\Console\Commands;

use App\Services\NovaPromptLoader;
use App\Services\NovaServicesPromptCatalog;
use Illuminate\Console\Command;

class InstallNovaPrompts extends Command
{
    protected $signature = 'nova:install-prompts {--force : Reinstall all prompts, resetting any manual edits}';

    protected $description = 'Install editable MCP prompts for App\Services\Nova classes into the database';

    public function handle(NovaServicesPromptCatalog $catalog): int
    {
        if ($this->option('force')) {
            $this->comment('Reinstalling Nova service prompts (overwriting existing)…');
            $result = $catalog->reinstall();
            NovaPromptLoader::clearCache();
            $this->info("✓ Server: {$result['server']->name}");
            $this->info("✓ Updated: {$result['updated']} prompts");
        } else {
            $this->comment('Installing Nova service prompts (skipping existing)…');
            $result = $catalog->install();
            NovaPromptLoader::clearCache();
            $this->info("✓ Server: {$result['server']->name}");
            $this->info("✓ Installed: {$result['installed']} prompts");
            $this->info("  Skipped:   {$result['skipped']} (already exist)");
        }

        $this->newLine();
        $this->line('Edit prompts at: /admin/prompts (filter by server "Nova Services")');

        return self::SUCCESS;
    }
}
