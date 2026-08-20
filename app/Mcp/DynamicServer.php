<?php

namespace App\Mcp;

use App\Models\Server as ServerModel;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Contracts\Transport;

class DynamicServer extends Server
{
    protected ?ServerModel $serverModel = null;

    /**
     * Static registry mapping endpoints to server IDs
     */
    protected static array $endpointMap = [];

    public function __construct(Transport $transport)
    {
        parent::__construct($transport);

        $this->resolveServerModel();

        if ($this->serverModel) {
            $this->name = $this->serverModel->name;
            $this->version = $this->serverModel->version;
            $this->instructions = $this->serverModel->instructions ?? '';
            $this->hydratePrimitives();
        }
    }

    /**
     * Register a server endpoint mapping
     */
    public static function registerEndpoint(string $endpoint, int $serverId): void
    {
        static::$endpointMap[$endpoint] = $serverId;
    }

    /**
     * Resolve the server model based on the current request
     */
    protected function resolveServerModel(): void
    {
        $request = request();
        $path = trim($request->path(), '/');

        // Try to find server by endpoint
        if (isset(static::$endpointMap[$path])) {
            $this->serverModel = ServerModel::find(static::$endpointMap[$path]);
        } else {
            // Fallback: try to match by endpoint pattern
            $this->serverModel = ServerModel::where(function ($query) use ($path): void {
                $query->where('endpoint', $path)
                    ->orWhere('endpoint', '/'.$path);
            })
                ->where('is_active', true)
                ->first();
        }
    }

    public function tools(): array
    {
        if (! $this->serverModel) {
            return [];
        }

        return $this->serverModel->tools()
            ->where('is_active', true)
            ->get()
            ->map(fn ($tool) => new DynamicTool($tool))
            ->all();
    }

    public function resources(): array
    {
        if (! $this->serverModel) {
            return [];
        }

        return $this->serverModel->resources()
            ->where('is_active', true)
            ->get()
            ->map(fn ($resource) => filled($resource->uri_template)
                ? new DynamicResourceTemplate($resource)
                : new DynamicResource($resource))
            ->all();
    }

    public function prompts(): array
    {
        if (! $this->serverModel) {
            return [];
        }

        return $this->serverModel->prompts()
            ->where('is_active', true)
            ->get()
            ->map(fn ($prompt) => new DynamicPrompt($prompt))
            ->all();
    }

    protected function hydratePrimitives(): void
    {
        $this->tools = $this->tools();
        $this->resources = $this->resources();
        $this->prompts = $this->prompts();
    }
}
