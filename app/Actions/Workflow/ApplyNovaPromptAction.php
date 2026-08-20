<?php

namespace App\Actions\Workflow;

use App\Models\Prompt;
use App\Models\Server;

class ApplyNovaPromptAction
{
    /**
     * Called by the workflow "Action" node.
     *
     * Expected payload keys:
     *   - server_slug : string (slug del server MCP)
     *   - prompt_name : string (nombre del prompt a aplicar, opcional)
     */
    public function __invoke(array $payload): array
    {
        $serverSlug = $payload['server_slug'] ?? null;
        $promptName = $payload['prompt_name'] ?? null;

        if (! $serverSlug) {
            return ['error' => 'server_slug is required.'];
        }

        try {
            $server = Server::where('slug', $serverSlug)->first();

            if (! $server) {
                return ['error' => "Server not found: {$serverSlug}"];
            }

            $query = Prompt::where('server_id', $server->id)
                ->where('is_active', true);

            if ($promptName) {
                $query->where('name', $promptName);
            }

            $prompt = $query->orderBy('sort_order')->first();

            if (! $prompt) {
                return [
                    'success' => true,
                    'prompt_applied' => false,
                    'message' => "No active prompt found for server: {$serverSlug}",
                ];
            }

            return [
                'success' => true,
                'prompt_applied' => true,
                'prompt' => [
                    'name' => $prompt->name,
                    'title' => $prompt->title,
                    'description' => $prompt->description,
                    'arguments' => $prompt->arguments,
                    'messages' => $prompt->messages,
                    'metadata' => $prompt->metadata,
                ],
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
