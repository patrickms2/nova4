<?php

namespace App\Livewire\Comunigest;

use App\Models\Photo;
use App\Models\WorkOrder;
use App\Models\WorkOrderTask;
use App\Support\CommunityPortalContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class WorkOrderDetail extends Component
{
    use WithFileUploads;

    public WorkOrder $workOrder;

    public string $elapsed = '00:00:00';

    public bool $showPhotoModal = false;

    public ?int $selectedTaskId = null;

    public $photoFile = null;

    public bool $showPhotos = false;

    public bool $showIncidents = false;

    public function mount(WorkOrder $workOrder): void
    {
        //abort_unless(CommunityPortalContext::canAccessWorkOrder($workOrder), 403);
        $this->workOrder = $workOrder->load(['community', 'tasks' => fn ($q) => $q->orderBy('sort'), 'photos', 'incidents' => fn ($q) => $q->with('photos')]);
        $this->computeElapsed();
    }

    public function computeElapsed(): void
    {
        if ($this->workOrder->started_at) {
            $this->elapsed = gmdate('H:i:s', (int) $this->workOrder->started_at->diffInSeconds(now()));
        } else {
            $this->elapsed = '00:00:00';
        }
    }

    public function startOrder(): void
    {
        $this->workOrder->status = 'in_progress';
        $this->workOrder->started_by = Auth::id();
        $this->workOrder->started_at = now();
        $this->workOrder->save();
        $this->workOrder->refresh();
        $this->computeElapsed();
    }

    public function toggleTask(int $taskId): void
    {
        $task = WorkOrderTask::where('work_order_id', $this->workOrder->id)->findOrFail($taskId);

        if ($task->status === 'completed') {
            $task->status = 'pending';
            $task->completed_by = null;
            $task->completed_at = null;
            $task->result = null;
        } else {
            $task->status = 'completed';
            $task->completed_by = Auth::id();
            $task->completed_at = now();
            $task->result = 'correcto';
        }

        $task->save();
        $this->workOrder->load(['tasks' => fn ($q) => $q->orderBy('sort')]);
    }

    public function finishOrder(): void
    {
        $this->workOrder->status = 'finished';
        $this->workOrder->finished_by = Auth::id();
        $this->workOrder->finished_at = now();
        $this->workOrder->save();
        $this->workOrder->refresh();
    }

    public function openPhotoModal(): void
    {
        $this->selectedTaskId = $this->workOrder->tasks->first()?->id;
        $this->showPhotoModal = true;
    }

    public function closePhotoModal(): void
    {
        $this->showPhotoModal = false;
        $this->reset('photoFile', 'selectedTaskId');
    }

    public function openPhotos(): void
    {
        $this->showPhotos = true;
    }

    public function closePhotos(): void
    {
        $this->showPhotos = false;
    }

    public function openIncidents(): void
    {
        $this->showIncidents = true;
    }

    public function closeIncidents(): void
    {
        $this->showIncidents = false;
    }

    public function uploadPhoto(): void
    {
        $this->validate([
            'photoFile' => 'required|image|max:5120',
            'selectedTaskId' => 'required|exists:work_order_tasks,id',
        ]);

        $path = $this->photoFile->store('comunigest/photos', 'public');

        Photo::create([
            'community_id' => $this->workOrder->community_id,
            'work_order_id' => $this->workOrder->id,
            'work_order_task_id' => $this->selectedTaskId,
            'path' => $path,
            'filename' => $this->photoFile->getClientOriginalName(),
            'mime_type' => $this->photoFile->getMimeType(),
            'size' => $this->photoFile->getSize(),
            'uploaded_by' => Auth::id(),
            'active' => true,
        ]);

        $this->workOrder->load('photos');
        $this->closePhotoModal();
    }

    public function render()
    {
        $this->computeElapsed();

        return view('livewire.comunigest.work-order-detail', [
            'photosCount' => $this->workOrder->photos()->count(),
            'incidentsCount' => $this->workOrder->incidents()->count(),
        ])->layout('layouts.mobile');
    }
}
