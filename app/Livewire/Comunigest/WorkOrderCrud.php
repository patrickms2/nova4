<?php

namespace App\Livewire\Comunigest;

use App\Models\Community;
use App\Models\WorkOrder;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Carbon;

class WorkOrderCrud extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public array $expanded = [];

    public string $search = '';

    public string $statusFilter = '';

    public ?int $communityFilter = null;

    public ?int $workOrderId = null;

    public ?int $communityId = null;

    public string $workDate = '';

    public string $status = 'pending';

    public string $requesterName = '';

    public string $requesterPhone = '';

    public string $reference = '';

    public bool $showComments = false;

    public ?WorkOrder $selectedOrderForComments = null;

    public string $newComment = '';

    public function mount(): void
    {
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->reset(['workOrderId', 'communityId', 'workDate', 'status', 'requesterName', 'requesterPhone', 'reference']);
        $this->workOrderId = null;
        $this->communityId = null;
        $this->workDate = now()->format('Y-m-d');
        $this->status = 'pending';
    }

    public function openNew(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $order = WorkOrder::findOrFail($id);
        $this->workOrderId = $order->id;
        $this->communityId = $order->community_id;
        $this->workDate = $order->work_date->format('Y-m-d');
        $this->status = $order->status;
        $this->requesterName = $order->requester_name ?? '';
        $this->requesterPhone = $order->requester_phone ?? '';
        $this->reference = $order->reference ?? '';
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function openComments(int $id): void
    {
        $this->selectedOrderForComments = WorkOrder::with('comments.user')->findOrFail($id);
        $this->newComment = '';
        $this->showComments = true;
    }

    public function closeComments(): void
    {
        $this->showComments = false;
        $this->selectedOrderForComments = null;
        $this->newComment = '';
    }

    public function saveComment(): void
    {
        $this->validate([
            'newComment' => 'required|string|max:2000',
            'selectedOrderForComments' => 'required',
        ]);

        \App\Models\WorkOrderComment::create([
            'work_order_id' => $this->selectedOrderForComments->id,
            'user_id' => auth()->id() ?? 1,
            'body' => $this->newComment,
        ]);

        $this->selectedOrderForComments->load('comments.user');
        $this->newComment = '';
    }

    public function toggleRead(int $commentId): void
    {
        $comment = \App\Models\WorkOrderComment::where('work_order_id', $this->selectedOrderForComments->id)->findOrFail($commentId);
        $comment->update(['is_read' => ! $comment->is_read]);
        $this->selectedOrderForComments->load('comments.user');
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'statusFilter', 'communityFilter');
        $this->resetPage();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'communityId' => 'required|exists:communities,id',
            'workDate' => 'required|date',
            'status' => 'required|in:pending,in_progress,finished,cancelled',
            'requesterName' => 'nullable|string',
            'requesterPhone' => 'nullable|string',
            'reference' => 'nullable|string',
        ]);

        $data = [
            'community_id' => $validated['communityId'],
            'work_date' => $validated['workDate'],
            'status' => $validated['status'],
            'requester_name' => $validated['requesterName'] ?: null,
            'requester_phone' => $validated['requesterPhone'] ?: null,
            'reference' => $validated['reference'] ?: null,
        ];

        if ($this->workOrderId) {
            WorkOrder::findOrFail($this->workOrderId)->update($data);
        } else {
            $fecha = Carbon::parse($validated['workDate']);
            $count = WorkOrder::where('work_date', $validated['workDate'])->count() + 1;
            $data['code'] = 'OT-'.$fecha->format('Ymd').'-'.str_pad((string) $count, 3, '0', STR_PAD_LEFT);
            WorkOrder::create($data);
        }

        $this->closeForm();
    }

    public function delete(int $id): void
    {
        WorkOrder::findOrFail($id)->delete();
        $this->closeForm();
    }

    public function toggle(int $id): void
    {
        $key = array_search($id, $this->expanded, true);

        if ($key === false) {
            $this->expanded[] = $id;
        } else {
            unset($this->expanded[$key]);
        }
    }

    public function render()
    {
        $query = WorkOrder::with(['community', 'tasks' => fn ($q) => $q->orderBy('sort'), 'incidents'])->withCount('comments');


        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', '%'.$this->search.'%')
                    ->orWhere('requester_name', 'like', '%'.$this->search.'%')
                    ->orWhere('reference', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->communityFilter) {
            $query->where('community_id', $this->communityFilter);
        }

        return view('livewire.comunigest.work-order-crud', [
            'workOrders' => $query->orderByDesc('id')->paginate(10),
            'communities' => Community::orderBy('name')->pluck('name', 'id')->toArray(),
        ])->layout('layouts.front');
    }
}
