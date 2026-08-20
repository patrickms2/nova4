<?php

namespace App\Livewire\Comunigest;

use App\Models\Community;
use App\Models\Incident;
use App\Models\WorkOrder;
use App\Models\WorkOrderTask;
use Livewire\Component;
use Livewire\WithPagination;

class IncidentCrud extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public bool $showPhotos = false;

    public ?Incident $selectedIncident = null;

    public bool $showComments = false;

    public ?Incident $selectedIncidentForComments = null;

    public string $newComment = '';

    public string $search = '';

    public string $statusFilter = '';

    public string $priorityFilter = '';

    public ?int $communityFilter = null;

    public ?int $incidentId = null;

    public ?int $communityId = null;

    public ?int $workOrderId = null;

    public ?int $workOrderTaskId = null;

    public string $title = '';

    public string $description = '';

    public string $priority = 'normal';

    public string $status = 'open';

    public string $resolutionNote = '';

    public function mount(): void
    {
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->reset(['incidentId', 'communityId', 'workOrderId', 'workOrderTaskId', 'title', 'description', 'priority', 'status', 'resolutionNote']);
        $this->incidentId = null;
        $this->communityId = null;
        $this->workOrderId = null;
        $this->workOrderTaskId = null;
        $this->priority = 'normal';
        $this->status = 'open';
    }

    public function openNew(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $incident = Incident::findOrFail($id);
        $this->incidentId = $incident->id;
        $this->communityId = $incident->community_id;
        $this->workOrderId = $incident->work_order_id;
        $this->workOrderTaskId = $incident->work_order_task_id;
        $this->title = $incident->title;
        $this->description = $incident->description ?? '';
        $this->priority = $incident->priority;
        $this->status = $incident->status;
        $this->resolutionNote = $incident->resolution_note ?? '';
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function openPhotos(int $id): void
    {
        $this->selectedIncident = Incident::with('photos')->findOrFail($id);
        $this->showPhotos = true;
    }

    public function closePhotos(): void
    {
        $this->showPhotos = false;
        $this->selectedIncident = null;
    }

    public function openComments(int $id): void
    {
        $this->selectedIncidentForComments = Incident::with('comments.user')->findOrFail($id);
        $this->newComment = '';
        $this->showComments = true;
    }

    public function closeComments(): void
    {
        $this->showComments = false;
        $this->selectedIncidentForComments = null;
        $this->newComment = '';
    }

    public function saveComment(): void
    {
        $this->validate([
            'newComment' => 'required|string|max:2000',
            'selectedIncidentForComments' => 'required',
        ]);

        \App\Models\IncidentComment::create([
            'incident_id' => $this->selectedIncidentForComments->id,
            'user_id' => auth()->id() ?? 1,
            'body' => $this->newComment,
        ]);

        $this->selectedIncidentForComments->load('comments.user');
        $this->newComment = '';
    }

    public function toggleRead(int $commentId): void
    {
        $comment = \App\Models\IncidentComment::where('incident_id', $this->selectedIncidentForComments->id)->findOrFail($commentId);
        $comment->update(['is_read' => ! $comment->is_read]);
        $this->selectedIncidentForComments->load('comments.user');
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'statusFilter', 'priorityFilter', 'communityFilter');
        $this->resetPage();
    }

    public function updatedWorkOrderId($value): void
    {
        $this->workOrderTaskId = null;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'communityId' => 'required|exists:communities,id',
            'workOrderId' => 'nullable|exists:work_orders,id',
            'workOrderTaskId' => 'nullable|exists:work_order_tasks,id',
            'title' => 'required|string',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,normal,high,urgent',
            'status' => 'required|in:open,assigned,communicated,resolved,closed',
            'resolutionNote' => 'nullable|string',
        ]);

        $data = [
            'community_id' => $validated['communityId'],
            'work_order_id' => $validated['workOrderId'] ?: null,
            'work_order_task_id' => $validated['workOrderTaskId'] ?: null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?: null,
            'priority' => $validated['priority'],
            'status' => $validated['status'],
        ];

        if ($this->incidentId) {
            $incident = Incident::findOrFail($this->incidentId);

            if ($validated['status'] === 'closed' && $incident->status !== 'closed') {
                $data['resolved_at'] = now();
                $data['resolved_by'] = auth()->id() ?? 1;
            }

            if ($validated['resolutionNote'] !== '') {
                $data['resolution_note'] = $validated['resolutionNote'];
            }

            $incident->update($data);
        } else {
            $data['created_by'] = auth()->id() ?? 1;
            Incident::create($data);
        }

        $this->closeForm();
    }

    public function delete(int $id): void
    {
        Incident::findOrFail($id)->delete();
        $this->closeForm();
    }

    public function render()
    {
        $query = Incident::with('community', 'workOrder')->withCount('photos', 'comments');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->priorityFilter) {
            $query->where('priority', $this->priorityFilter);
        }

        if ($this->communityFilter) {
            $query->where('community_id', $this->communityFilter);
        }

        return view('livewire.comunigest.incident-crud', [
            'incidents' => $query->orderByDesc('id')->paginate(10),
            'communities' => Community::orderBy('name')->pluck('name', 'id')->toArray(),
            'workOrders' => WorkOrder::orderByDesc('id')->pluck('code', 'id')->toArray(),
            'tasks' => $this->workOrderId
                ? WorkOrderTask::where('work_order_id', $this->workOrderId)->pluck('title', 'id')->toArray()
                : [],
        ])->layout('layouts.front');
    }
}
