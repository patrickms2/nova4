<?php

namespace App\Livewire\Comunigest;

use App\Models\WorkOrder;
use App\Models\WorkOrderTask;
use Livewire\Component;
use Livewire\WithPagination;

class WorkOrderTaskCrud extends Component
{
    use WithPagination;

    public WorkOrder $workOrder;

    public ?int $taskId = null;

    public string $title = '';

    public string $instructions = '';

    public string $requirements = '';

    public string $priority = 'normal';

    public string $status = 'pending';

    public string $result = '';

    public string $requesterName = '';

    public string $requesterPhone = '';

    public string $reference = '';

    public int $sort = 0;

    public bool $showComments = false;

    public ?WorkOrderTask $selectedTaskForComments = null;

    public string $newComment = '';

    public function mount(int $orderId): void
    {
        $order = WorkOrder::with([
            'tasks' => fn ($q) => $q->withCount('comments'),
            'community',
            'incidents',
        ])->withCount('tasks')->findOrFail($orderId);

        $this->workOrder = $order;
    }

    public function resetForm(): void
    {
        $this->reset(['taskId', 'title', 'instructions', 'requirements', 'priority', 'status', 'result', 'requesterName', 'requesterPhone', 'reference', 'sort']);
        $this->taskId = null;
        $this->priority = 'normal';
        $this->status = 'pending';
        $this->sort = $this->workOrder->tasks()->max('sort') + 1;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => 'required|string',
            'instructions' => 'nullable|string',
            'requirements' => 'nullable|string',
            'priority' => 'required|in:low,normal,high,urgent',
            'status' => 'required|in:pending,completed,not_done,cancelled',
            'result' => 'nullable|in:correcto,con_observaciones,no_realizado,requiere_seguimiento',
            'requesterName' => 'nullable|string',
            'requesterPhone' => 'nullable|string',
            'reference' => 'nullable|string',
            'sort' => 'required|integer',
        ]);

        $data = [
            'work_order_id' => $this->workOrder->id,
            'source_type' => $this->taskId ? $this->workOrder->tasks()->findOrFail($this->taskId)->source_type : 'EXTRA',
            'title' => $validated['title'],
            'instructions' => $validated['instructions'] ?: null,
            'requirements' => $validated['requirements'] ?: null,
            'priority' => $validated['priority'],
            'status' => $validated['status'],
            'result' => $validated['result'] ?: null,
            'requester_name' => $validated['requesterName'] ?: null,
            'requester_phone' => $validated['requesterPhone'] ?: null,
            'reference' => $validated['reference'] ?: null,
            'sort' => $validated['sort'],
        ];

        if ($this->taskId) {
            $task = WorkOrderTask::findOrFail($this->taskId);

            if ($validated['status'] === 'completed' && $task->status !== 'completed') {
                $data['completed_at'] = now();
                $data['completed_by'] = auth()->id() ?? 1;
            }

            $task->update($data);
        } else {
            $data['created_by'] = auth()->id() ?? 1;
            WorkOrderTask::create($data);
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $task = WorkOrderTask::findOrFail($id);
        $this->taskId = $task->id;
        $this->title = $task->title;
        $this->instructions = $task->instructions ?? '';
        $this->requirements = $task->requirements ?? '';
        $this->priority = $task->priority;
        $this->status = $task->status;
        $this->result = $task->result ?? '';
        $this->requesterName = $task->requester_name ?? '';
        $this->requesterPhone = $task->requester_phone ?? '';
        $this->reference = $task->reference ?? '';
        $this->sort = $task->sort;
    }

    public function delete(int $id): void
    {
        WorkOrderTask::findOrFail($id)->delete();
    }

    public function openComments(int $id): void
    {
        $this->selectedTaskForComments = WorkOrderTask::with('comments.user')->findOrFail($id);
        $this->newComment = '';
        $this->showComments = true;
    }

    public function closeComments(): void
    {
        $this->showComments = false;
        $this->selectedTaskForComments = null;
        $this->newComment = '';
    }

    public function saveComment(): void
    {
        $this->validate([
            'newComment' => 'required|string|max:2000',
            'selectedTaskForComments' => 'required',
        ]);

        \App\Models\TaskComment::create([
            'work_order_task_id' => $this->selectedTaskForComments->id,
            'user_id' => auth()->id() ?? 1,
            'body' => $this->newComment,
        ]);

        $this->selectedTaskForComments->load('comments.user');
        $this->newComment = '';
    }

    public function toggleRead(int $commentId): void
    {
        $comment = \App\Models\TaskComment::where('work_order_task_id', $this->selectedTaskForComments->id)->findOrFail($commentId);
        $comment->update(['is_read' => ! $comment->is_read]);
        $this->selectedTaskForComments->load('comments.user');
    }

    public function render()
    {
        return view('livewire.comunigest.work-order-task-crud', [
            'tasks' => $this->workOrder->with('tasks','incidents')->orderBy('created_at')->paginate(10),
        ])->layout('layouts.front');
    }
}
