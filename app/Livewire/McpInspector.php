<?php

namespace App\Livewire;

use App\Models\Server;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class McpInspector extends Component
{
    public ?int $serverId = null;

    public ?Server $server = null;

    public array $capabilities = [];

    public array $toolsList = [];

    public array $resourcesList = [];

    public array $promptsList = [];

    public string $activeTab = 'overview';

    public bool $isConnected = false;

    public ?string $error = null;

    public function mount(?int $serverId = null): void
    {
        if ($serverId) {
            $this->serverId = $serverId;
            $this->loadServer();
        }
    }

    public function loadServer(): void
    {
        $this->server = Server::with(['tools', 'resources', 'prompts'])
            ->find($this->serverId);

        if ($this->server) {
            $this->connect();
        }
    }

    public function connect(): void
    {
        try {
            $endpoint = url($this->server->endpoint);

            // Initialize MCP connection
            $response = Http::post($endpoint, [
                'jsonrpc' => '2.0',
                'method' => 'initialize',
                'params' => [
                    'protocolVersion' => '2025-03-26',
                    'capabilities' => [],
                    'clientInfo' => [
                        'name' => 'MCP Studio Inspector',
                        'version' => '1.0.0',
                    ],
                ],
                'id' => 1,
            ]);

            if ($response->successful()) {
                $result = $response->json('result');
                $this->capabilities = $result['capabilities'] ?? [];
                $this->isConnected = true;
                $this->loadLists();
            }
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
            $this->isConnected = false;
        }
    }

    public function loadLists(): void
    {
        $this->toolsList = $this->server->tools
            ->where('is_active', true)
            ->toArray();
        $this->resourcesList = $this->server->resources
            ->where('is_active', true)
            ->toArray();
        $this->promptsList = $this->server->prompts
            ->where('is_active', true)
            ->toArray();
    }

    public function render()
    {
        return view('livewire.mcp-inspector', [
            'servers' => Server::where('is_active', true)->get(),
        ]);
    }
}
