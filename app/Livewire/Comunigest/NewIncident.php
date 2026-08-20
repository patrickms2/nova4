<?php

namespace App\Livewire\Comunigest;

use App\Models\Incident;
use App\Models\Photo;
use App\Models\WorkOrder;
use App\Support\CommunityPortalContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class NewIncident extends Component
{
    use WithFileUploads;

    public ?WorkOrder $workOrder = null;

    public string $incidentType = 'Fuga de agua';

    public string $description = '';

    public string $priority = 'media';

    /** @var array<int, TemporaryUploadedFile> */
    public $photoFiles = [];

    public function mount(?WorkOrder $workOrder = null): void
    {
        /*if ($workOrder) {
            abort_unless(CommunityPortalContext::canAccessWorkOrder($workOrder), 403);
        }*/
        $this->workOrder = $workOrder;
    }

    protected function rules(): array
    {
        return [
            'incidentType' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'priority' => 'required|in:baja,media,alta,urgente',
            'photoFiles' => 'nullable|array|max:5',
            'photoFiles.*' => 'image|max:5120',
        ];
    }

    public function removePhoto(int $index): void
    {
        unset($this->photoFiles[$index]);
        $this->photoFiles = array_values($this->photoFiles);
    }

    public function save(): void
    {
        $this->validate();

        if (! $this->workOrder) {
            $this->addError('workOrder', 'No se ha seleccionado una orden de trabajo.');

            return;
        }

        $incident = new Incident;
        $incident->community_id = $this->workOrder->community_id;
        $incident->work_order_id = $this->workOrder->id;
        $incident->title = $this->incidentType;
        $incident->description = $this->description;
        $incident->priority = $this->priority;
        $incident->status = 'open';
        $incident->created_by = Auth::id();
        $incident->updated_by = Auth::id();
        $incident->save();

        foreach ($this->photoFiles as $file) {
            $path = $file->store('comunigest/photos', 'public');

            Photo::create([
                'community_id' => $this->workOrder->community_id,
                'work_order_id' => $this->workOrder->id,
                'incident_id' => $incident->id,
                'path' => $path,
                'filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => Auth::id(),
                'active' => true,
            ]);
        }

        $this->redirect(route('comunigest.work-order', $this->workOrder), navigate: true);
    }

    public function render()
    {
        return view('livewire.comunigest.new-incident', [
            'communityName' => $this->workOrder?->community?->name ?? 'Comunidad',
        ])->layout('layouts.mobile');
    }
}
