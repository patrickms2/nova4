<?php

declare(strict_types=1);

namespace App\Services\Nova;

use App\Models\Server;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class NovaMcpClient
{
    private ?Server $server;

    private int $timeout = 30;

    private int $retries = 3;

    private int $jsonRpcId = 1;

    public function __construct(?Server $server = null)
    {
        $this->server = $server;
    }

    public function setServer(Server $server): self
    {
        $this->server = $server;

        return $this;
    }

    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;

        return $this;
    }

    public function setRetries(int $count): self
    {
        $this->retries = $count;

        return $this;
    }

    /**
     * Get MCP server info
     */
    public function getInfo(): array
    {
        $this->ensureServer();

        try {
            $response = $this->http()->get('/info');

            $response->throw();

            return $response->json();
        } catch (\Throwable $exception) {
            Log::error('Nova MCP getInfo failed', [
                'server_id' => $this->server->id,
                'server_type' => $this->server->type,
                'endpoint' => $this->server->endpoint,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Get available MCP tools
     */
    public function getTools(): array
    {
        $this->ensureServer();

        try {
            $response = $this->http()->get('/tools');

            $response->throw();

            return $response->json();
        } catch (\Throwable $exception) {
            Log::error('Nova MCP getTools failed', [
                'server_id' => $this->server->id,
                'server_type' => $this->server->type,
                'endpoint' => $this->server->endpoint,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * List tools from an MCP JSON-RPC endpoint.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listJsonRpcTools(): array
    {
        $result = $this->jsonRpc('tools/list');

        return (array) data_get($result, 'tools', []);
    }

    /**
     * Execute a tool on an MCP JSON-RPC endpoint.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function callJsonRpcTool(string $toolName, array $arguments = []): array
    {
        $result = $this->jsonRpc('tools/call', [
            'name' => $toolName,
            'arguments' => $arguments,
        ]);

        return $this->decodeJsonRpcToolResult($result);
    }

    /**
     * Execute an MCP tool
     */
    public function executeTool(string $toolName, array $arguments = []): array
    {
        $this->ensureServer();

        try {
            $response = $this->http()->post('/execute', [
                'tool' => $toolName,
                'arguments' => $arguments,
            ]);

            $response->throw();

            $result = $response->json();

            // Update last_checked_at on successful execution
            $this->server->update(['last_checked_at' => now()]);

            return $result;
        } catch (\Throwable $exception) {
            Log::error('Nova MCP executeTool failed', [
                'server_id' => $this->server->id,
                'server_type' => $this->server->type,
                'endpoint' => $this->server->endpoint,
                'tool' => $toolName,
                'arguments' => $arguments,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $exception->getMessage(),
                'tool' => $toolName,
                'arguments' => $arguments,
            ];
        }
    }

    /**
     * Check if MCP server is healthy
     */
    public function healthCheck(): bool
    {
        $this->ensureServer();

        try {
            $response = $this->http()->timeout(5)->get('/info');

            return $response->successful();
        } catch (\Throwable $exception) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function jsonRpc(string $method, array $params = []): array
    {
        $this->ensureServer();

        try {
            $response = $this->jsonRpcHttp()->post('', [
                'jsonrpc' => '2.0',
                'id' => $this->jsonRpcId++,
                'method' => $method,
                'params' => $params,
            ]);

            $response->throw();

            $payload = $response->json();

            if (is_array($payload) && isset($payload['error'])) {
                throw new \RuntimeException((string) data_get($payload, 'error.message', 'MCP JSON-RPC error'));
            }

            if ($this->server->exists) {
                $this->server->forceFill([
                    'last_checked_at' => now(),
                    'last_error' => null,
                ])->save();
            }

            return (array) data_get($payload, 'result', []);
        } catch (\Throwable $exception) {
            Log::error('Nova MCP JSON-RPC request failed', [
                'server_id' => $this->server->id,
                'server_type' => $this->server->type,
                'endpoint' => $this->server->endpoint,
                'method' => $method,
                'params' => $params,
                'error' => $exception->getMessage(),
            ]);

            if ($this->server->exists) {
                $this->server->forceFill([
                    'last_error' => $exception->getMessage(),
                ])->save();
            }

            return [
                'success' => false,
                'error' => $exception->getMessage(),
                'method' => $method,
                'params' => $params,
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function decodeJsonRpcToolResult(array $result): array
    {
        $content = data_get($result, 'content.0.text');

        if (is_string($content)) {
            $decoded = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }

            return ['text' => $content];
        }

        return $result;
    }

    private function http(): PendingRequest
    {
        $baseUrl = rtrim($this->server->endpoint, '/');

        $request = Http::baseUrl($baseUrl)
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout)
            ->retry($this->retries, 100);

        // Add authentication if credentials exist
        $credentials = $this->server->credentials ?? [];
        if (filled($credentials)) {
            $token = data_get($credentials, 'token', data_get($credentials, 'api_key', ''));
            if ($token !== '') {
                $request = $request->withToken($token);
            }

            $apiKey = data_get($credentials, 'api_key');
            if ($apiKey !== '' && $token === '') {
                $request = $request->withHeaders(['X-API-Key' => $apiKey]);
            }
        }

        return $request;
    }

    private function jsonRpcHttp(): PendingRequest
    {
        $request = Http::acceptJson()
            ->asJson()
            ->timeout($this->timeout)
            ->retry($this->retries, 100);

        $credentials = $this->server->credentials ?? [];
        if (filled($credentials)) {
            $token = data_get($credentials, 'token', data_get($credentials, 'api_key', ''));
            if ($token !== '') {
                $request = $request->withToken($token);
            }

            $apiKey = data_get($credentials, 'api_key');
            if ($apiKey !== '' && $token === '') {
                $request = $request->withHeaders(['X-API-Key' => $apiKey]);
            }

            $headers = data_get($credentials, 'headers', []);
            if (is_array($headers) && $headers !== []) {
                $request = $request->withHeaders($headers);
            }
        }

        return $request->baseUrl(rtrim($this->server->endpoint, '/'));
    }

    private function ensureServer(): void
    {
        if ($this->server === null) {
            throw new \RuntimeException('MCP server not set. Call setServer() first.');
        }
    }
}
