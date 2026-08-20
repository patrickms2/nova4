<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\ExternalSync\ExternalSourceRegistrar;
use Illuminate\Console\Command;

class RegisterExternalSources extends Command
{
    protected $signature = 'external-sync:register-sources {--server= : Register sources for one server ID}';

    protected $description = 'Register local external sync sources from MCP server metadata';

    public function handle(ExternalSourceRegistrar $registrar): int
    {
        $serverId = $this->option('server');

        if ($serverId) {
            $server = Server::query()->findOrFail($serverId);
            $sources = $registrar->registerForServer($server);
        } else {
            $sources = $registrar->registerAll();
        }

        $count = $sources->count();
        $this->info("Registered {$count} external source(s).");

        return self::SUCCESS;
    }
}
