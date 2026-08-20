<?php

namespace App\Livewire;

use App\Models\McpLog;
use Livewire\Component;
use Livewire\WithPagination;

class McpLogViewer extends Component
{
    use WithPagination;

    public ?int $serverId = null;

    public ?string $typeFilter = null;

    public ?string $searchQuery = null;

    public bool $autoRefresh = false;

    protected $queryString = [
        'serverId' => ['except' => null],
        'typeFilter' => ['except' => null],
        'searchQuery' => ['except' => null],
    ];

    public function render()
    {
        $logs = McpLog::with(['server', 'tool'])
            ->when($this->serverId, fn ($q) => $q->where('server_id', $this->serverId))
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->searchQuery, fn ($q) => $q->where(function ($query) {
                $query->whereJsonContains('request_data', $this->searchQuery)
                    ->orWhere('error_message', 'like', "%{$this->searchQuery}%");
            }))
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('livewire.mcp-log-viewer', [
            'logs' => $logs,
        ]);
    }
}
