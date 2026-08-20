<?php

namespace App\Services\ClickUp;

use Illuminate\Support\Facades\Http;

class ClickUpService
{
    public function __construct(
        protected ?string $apiToken = null,
        protected ?string $workspaceId = null,
        protected ?string $defaultListId = null,
    ) {
        $this->apiToken = $this->apiToken ?? config('services.clickup.api_token');
        $this->workspaceId = $this->workspaceId ?? config('services.clickup.workspace_id');
        $this->defaultListId = $this->defaultListId ?? config('services.clickup.default_list_id');
    }

    public function getWorkspaces(): array
    {
        return $this->request('team');
    }

    public function getLists(?string $workspaceId = null): array
    {
        $workspace = $workspaceId ?? $this->workspaceId;

        if (! $workspace) {
            return [];
        }

        return $this->request("team/{$workspace}/list");
    }

    public function createTask(array $payload): array
    {
        $listId = $payload['list_id'] ?? $this->defaultListId;

        if (! $listId) {
            throw new \InvalidArgumentException('No se ha configurado un list_id de ClickUp.');
        }

        $response = Http::withHeaders([
            'Authorization' => $this->apiToken,
            'Content-Type' => 'application/json',
        ])->post("https://api.clickup.com/api/v2/list/{$listId}/task", $payload);

        $response->throw();

        $data = $response->json();

        return [
            'id' => $data['id'] ?? null,
            'name' => $data['name'] ?? $payload['name'] ?? null,
            'status' => $data['status']['status'] ?? null,
            'url' => $data['url'] ?? null,
            'raw' => $data,
        ];
    }

    public function updateTask(string $taskId, array $payload): array
    {
        $response = Http::withHeaders([
            'Authorization' => $this->apiToken,
            'Content-Type' => 'application/json',
        ])->put("https://api.clickup.com/api/v2/task/{$taskId}", $payload);

        $response->throw();

        $data = $response->json();

        return [
            'id' => $data['id'] ?? $taskId,
            'name' => $data['name'] ?? null,
            'status' => $data['status']['status'] ?? null,
            'url' => $data['url'] ?? null,
            'raw' => $data,
        ];
    }

    public function getTasks(?string $listId = null): array
    {
        $list = $listId ?? $this->defaultListId;

        if (! $list) {
            return [];
        }

        $response = Http::withHeaders([
            'Authorization' => $this->apiToken,
            'Content-Type' => 'application/json',
        ])->get("https://api.clickup.com/api/v2/list/{$list}/task");

        $response->throw();

        $data = $response->json();

        return $data['tasks'] ?? [];
    }

    private function request(string $path): array
    {
        $response = Http::withHeaders([
            'Authorization' => $this->apiToken,
            'Content-Type' => 'application/json',
        ])->get("https://api.clickup.com/api/v2/{$path}");

        $response->throw();

        return $response->json();
    }
}
