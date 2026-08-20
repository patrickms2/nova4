<?php

namespace App\Livewire\Comunigest;

use App\Models\WorkCatalog;
use App\Models\WorkCategory;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class OrderTypeCrud extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public string $search = '';

    public ?int $catalogId = null;

    public ?int $workCategoryId = null;

    public ?string $code = null;

    public string $title = '';

    public string $instructions = '';

    public string $requirements = '';

    public string $defaultPriority = 'normal';

    public bool $active = true;

    public function mount(): void
    {
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->catalogId = null;
        $this->workCategoryId = null;
        $this->code = null;
        $this->title = '';
        $this->instructions = '';
        $this->requirements = '';
        $this->defaultPriority = 'normal';
        $this->active = true;
    }

    public function openNew(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $catalog = WorkCatalog::findOrFail($id);
        $this->catalogId = $catalog->id;
        $this->workCategoryId = $catalog->work_category_id;
        $this->code = $catalog->code;
        $this->title = $catalog->title;
        $this->instructions = $catalog->instructions ?? '';
        $this->requirements = $catalog->requirements ?? '';
        $this->defaultPriority = $catalog->default_priority;
        $this->active = $catalog->active;
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
            'workCategoryId' => 'required|exists:work_categories,id',
            'code' => 'nullable|string|max:50|unique:work_catalog,code,' . $this->catalogId,
            'title' => 'required|string|max:255',
            'instructions' => 'nullable|string',
            'requirements' => 'nullable|string',
            'defaultPriority' => 'required|in:low,normal,high,urgent',
            'active' => 'boolean',
        ]);

        $data = [
            'work_category_id' => $validated['workCategoryId'],
            'code' => $validated['code'] ?: null,
            'title' => $validated['title'],
            'instructions' => $validated['instructions'] ?: null,
            'requirements' => $validated['requirements'] ?: null,
            'default_priority' => $validated['defaultPriority'],
            'active' => $validated['active'],
        ];

        if ($this->catalogId) {
            $catalog = WorkCatalog::findOrFail($this->catalogId);
            $catalog->updated_by = Auth::id();
            $catalog->update($data);
        } else {
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();
            WorkCatalog::create($data);
        }

        $this->closeForm();
    }

    public function delete(int $id): void
    {
        WorkCatalog::findOrFail($id)->delete();
        $this->closeForm();
    }

    public function render()
    {
        $query = WorkCatalog::with('category');

        if ($this->search) {
            $query->where(fn ($q) => $q->where('title', 'like', '%' . $this->search . '%')->orWhere('code', 'like', '%' . $this->search . '%'));
        }

        return view('livewire.comunigest.order-type-crud', [
            'catalogs' => $query->orderBy('title')->paginate(20),
            'categories' => WorkCategory::where('active', true)->orderBy('sort')->orderBy('name')->get(),
        ])->layout('layouts.front');
    }
}
