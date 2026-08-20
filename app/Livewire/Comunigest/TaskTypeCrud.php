<?php

namespace App\Livewire\Comunigest;

use App\Models\WorkCategory;
use Livewire\Component;
use Livewire\WithPagination;

class TaskTypeCrud extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public string $search = '';

    public ?int $taskTypeId = null;

    public string $name = '';

    public int $sort = 0;

    public bool $active = true;

    public function mount(): void
    {
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->taskTypeId = null;
        $this->name = '';
        $this->sort = 0;
        $this->active = true;
    }

    public function openNew(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $taskType = WorkCategory::findOrFail($id);
        $this->taskTypeId = $taskType->id;
        $this->name = $taskType->name;
        $this->sort = $taskType->sort;
        $this->active = $taskType->active;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255|unique:work_categories,name,' . $this->taskTypeId,
            'sort' => 'nullable|integer|min:0',
            'active' => 'boolean',
        ]);

        $data = [
            'name' => $validated['name'],
            'sort' => $validated['sort'] ?? 0,
            'active' => $validated['active'],
        ];

        if ($this->taskTypeId) {
            WorkCategory::findOrFail($this->taskTypeId)->update($data);
        } else {
            WorkCategory::create($data);
        }

        $this->closeForm();
    }

    public function delete(int $id): void
    {
        WorkCategory::findOrFail($id)->delete();
        $this->closeForm();
    }

    public function render()
    {
        $query = WorkCategory::query();

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        return view('livewire.comunigest.task-type-crud', [
            'taskTypes' => $query->orderBy('sort')->orderBy('name')->paginate(20),
        ])->layout('layouts.front');
    }
}
