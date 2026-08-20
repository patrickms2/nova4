<?php

declare(strict_types=1);

namespace App\Services\Nova;

use App\Models\NovaMcpServer;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class NovaMcpClient
{
    private ?NovaMcpServer $server;

    private int $timeout = 30;

    private int $retries = 3;

    public function __construct(?NovaMcpServer $server = null)
    {
        $this->server = $server;
    }

    public function setServer(NovaMcpServer $server): self
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
                'endpoint' => $this->server->endpoint_url,
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
                'endpoint' => $this->server->endpoint_url,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
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
                'endpoint' => $this->server->endpoint_url,
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

    private function http(): PendingRequest
    {
        $baseUrl = rtrim($this->server->endpoint_url, '/');

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

    private function ensureServer(): void
    {
        if ($this->server === null) {
            throw new \RuntimeException('MCP server not set. Call setServer() first.');
        }
    }
}
