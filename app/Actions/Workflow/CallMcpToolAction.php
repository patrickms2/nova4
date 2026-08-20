<?php

namespace App\Actions\Workflow;

use App\Models\Server;
use App\Models\Tool;
use Illuminate\Support\Facades\Http;

class CallMcpToolAction
{
    /**
     * Called by the workflow "Action" node.
     *
     * Expected payload keys:
     *   - server_slug  : e.g. "taxilanz-hoteles-laravel-mcp"
     *   - tool_name    : e.g. "hotel_list"
     *   - input        : array of tool input params (optional)
     */
    public function __invoke(array $payload): array
    {
        $serverSlug = $payload['server_slug'] ?? null;
        $toolName = $payload['tool_name'] ?? null;
        $input = $payload['input'] ?? [];

        if (! $serverSlug || ! $toolName) {
            return ['error' => 'server_slug and tool_name are required.'];
        }

        $server = Server::where('slug', $serverSlug)
            ->where('is_active', true)
            ->first();

        if (! $server) {
            return ['error' => "Server [{$serverSlug}] not found or inactive."];
        }

        $tool = Tool::where('server_id', $server->id)
            ->where('name', $toolName)
            ->where('is_active', true)
            ->first();

        if (! $tool) {
            return ['error' => "Tool [{$toolName}] not found on server [{$serverSlug}]."];
        }

        $endpoint = rtrim(config('app.url'), '/').$server->endpoint;

        try {
            $request = Http::timeout(15);

            if (str_ends_with((string) parse_url($endpoint, PHP_URL_HOST), '.test')) {
                $request = $request->withoutVerifying();
            }

            $response = $request->post($endpoint, [
                'jsonrpc' => '2.0',
                'method' => 'tools/call',
                'id' => 1,
                'params' => [
                    'name' => $toolName,
                    'arguments' => (object) $input,
                ],
            ]);

            $body = $response->json();

            if (isset($body['result']['content'])) {
                $content = $body['result']['content'];

                if (is_array($content) && isset($content[0]['text'])) {
                    $text = $content[0]['text'];
                    
                    // If text is already an array, return it directly
                    if (is_array($text)) {
                        return ['result' => $text];
                    }
                    
                    // Otherwise try to decode as JSON
                    $decoded = json_decode($text, true);
                    return ['result' => $decoded ?? $text];
                }

                return ['result' => $content];
            }

            if (isset($body['error'])) {
                return ['error' => $body['error']['message'] ?? 'MCP error'];
            }

            return ['result' => $body];

        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
