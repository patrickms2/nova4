<?php

namespace App\Services;

use App\Mcp\DynamicServer;
use App\Models\Server;
use Laravel\Mcp\Facades\Mcp;

class McpServerGenerator
{
    public function registerAllServers(): void
    {
        Server::where('is_active', true)->each(function (Server $server) {

            $this->registerServer($server);
        });
    }

    public function registerServer(Server $server): void
    {
        $endpoint = ltrim($server->endpoint, '/');

        // Register endpoint mapping for dynamic resolution
        DynamicServer::registerEndpoint($endpoint, $server->id);

        if ($server->transport === 'web') {
            $registration = Mcp::web($endpoint, DynamicServer::class);
            if (! empty($server->middleware)) {
                $registration->middleware($server->middleware);
            }
        } else {
            Mcp::local($server->slug, DynamicServer::class);
        }
    }

    public function unregisterServer(Server $server): void
    {
        // MCP doesn't support runtime unregistration
        // This would require app restart or route refresh
    }

    public function generateServerConfig(Server $server): array
    {
        return [
            'name' => $server->name,
            'version' => $server->version,
            'endpoint' => $server->endpoint,
            'transport' => $server->transport,
            'tools' => $server->tools->count(),
            'resources' => $server->resources->count(),
            'prompts' => $server->prompts->count(),
        ];
    }
}
