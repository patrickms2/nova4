<?php

namespace App\Actions\Workflow;

use App\Models\Server;
use App\Models\Tool;

class ListMcpToolsAction
{
    /**
     * Called by the workflow "Action" node.
     *
     * Expected payload keys (all optional):
     *   - server_slug       : filter by server slug
     *   - nova_business_id  : filter by business
     *   - active_only       : bool (default true)
     */
    public function __invoke(array $payload): array
    {
        $serverSlug = $payload['server_slug'] ?? null;
        $businessId = $payload['nova_business_id'] ?? null;
        $activeOnly = $payload['active_only'] ?? true;

        $query = Tool::query()
            ->with('server:id,name,slug,nova_business_id')
            ->when($activeOnly, fn ($q) => $q->where('is_active', true));

        if ($serverSlug) {
            $server = Server::where('slug', $serverSlug)->first();
            $query->where('server_id', $server?->id ?? 0);
        } elseif ($businessId) {
            $query->whereHas('server', fn ($q) => $q->where('nova_business_id', $businessId));
        }

        $tools = $query->get(['id', 'server_id', 'name', 'title', 'description', 'is_active']);

        return [
            'count' => $tools->count(),
            'tools' => $tools->map(fn (Tool $t) => [
                'server' => $t->server?->slug,
                'name' => $t->name,
                'title' => $t->title,
                'description' => $t->description,
            ])->values()->all(),
        ];
    }
}
