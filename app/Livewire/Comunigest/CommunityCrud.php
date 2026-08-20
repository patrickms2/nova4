<?php

namespace App\Livewire\Comunigest;

use App\Models\Community;
use Livewire\Component;
use Livewire\WithPagination;

class CommunityCrud extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $communityId = null;

    public string $code = '';

    public string $name = '';

    public string $address = '';

    public string $contactName = '';

    public string $contactPhone = '';

    public string $notes = '';

    public string $status = 'active';

    public function mount(): void
    {
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->reset(['communityId', 'code', 'name', 'address', 'contactName', 'contactPhone', 'notes', 'status']);
        $this->communityId = null;
        $this->status = 'active';
    }

    public function openNew(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $community = Community::findOrFail($id);
        $this->communityId = $community->id;
        $this->code = $community->code;
        $this->name = $community->name;
        $this->address = $community->address ?? '';
        $this->contactName = $community->contact_name ?? '';
        $this->contactPhone = $community->contact_phone ?? '';
        $this->notes = $community->notes ?? '';
        $this->status = $community->status;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'statusFilter');
        $this->resetPage();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'code' => 'required|string|unique:communities,code,'.$this->communityId,
            'name' => 'required|string',
            'address' => 'nullable|string',
            'contactName' => 'nullable|string',
            'contactPhone' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $data = [
            'code' => $validated['code'],
            'name' => $validated['name'],
            'address' => $validated['address'] ?: null,
            'contact_name' => $validated['contactName'] ?: null,
            'contact_phone' => $validated['contactPhone'] ?: null,
            'notes' => $validated['notes'] ?: null,
            'status' => $validated['status'],
        ];

        if ($this->communityId) {
            Community::findOrFail($this->communityId)->update($data);
        } else {
            Community::create($data);
        }

        $this->closeForm();
    }

    public function delete(int $id): void
    {
        Community::findOrFail($id)->delete();
        $this->closeForm();
    }

    public function render()
    {
        $query = Community::query()->with('workOrders', 'workOrders.tasks', 'workOrders.incidents');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('code', 'like', '%'.$this->search.'%')
                    ->orWhere('address', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return view('livewire.comunigest.community-crud', [
            'communities' => $query->orderBy('name')->paginate(10),
        ])->layout('layouts.front');
    }
}
