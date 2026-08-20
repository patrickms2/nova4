<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\ProjectCategory;
use Livewire\Component;
use Livewire\WithPagination;

class Projects extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $categoryFilter = null;
    public ?string $phaseFilter = null;
    public ?string $statusFilter = null;

    public bool $showEditor = false;
    public bool $showCategoryModal = false;
    public ?int $editingId = null;

    public array $form = [
        'name' => '',
        'description' => '',
        'color' => '#3b82f6',
        'icon' => null,
        'status' => 'active',
        'phase' => 'planning',
        'parent_id' => null,
        'project_category_id' => null,
        'start_date' => null,
        'end_date' => null,
        'is_public' => false,
        'sort_order' => 0,
    ];

    public array $categoryForm = [
        'name' => '',
        'slug' => '',
        'description' => '',
        'color' => '#6366f1',
        'icon' => null,
        'sort_order' => 0,
    ];

    public $projects;
    public $categories;
    public $rootProjects;

    protected $rules = [
        'form.name' => 'required|string|max:255',
        'form.description' => 'nullable|string',
        'form.color' => 'required|string',
        'form.status' => 'required|in:active,archived',
        'form.phase' => 'required|in:planning,development,testing,deployment,completed',
        'form.start_date' => 'nullable|date',
        'form.end_date' => 'nullable|date|after_or_equal:form.start_date',
        'form.project_category_id' => 'nullable|exists:project_categories,id',
    ];

    protected $categoryRules = [
        'categoryForm.name' => 'required|string|max:255',
        'categoryForm.slug' => 'required|string|max:255|unique:project_categories,slug',
        'categoryForm.color' => 'required|string',
    ];

    public function mount(): void
    {
        $this->init();
    }

    public function init(): void
    {
        $this->projects = $this->getProjects();
        $this->categories = ProjectCategory::orderBy('sort_order')->get();
        $this->rootProjects = Project::root()->active()->orderBy('sort_order')->get();
    }

    public function getProjects()
    {
        return Project::query()
            ->with('category', 'parent', 'children', 'tasks')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('description', 'like', "%{$this->search}%")
            )
            ->when($this->categoryFilter, fn ($q) => $q->where('project_category_id', $this->categoryFilter))
            ->when($this->phaseFilter, fn ($q) => $q->where('phase', $this->phaseFilter))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy('sort_order')
            ->latest()
            ->get();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->categoryFilter = null;
        $this->phaseFilter = null;
        $this->statusFilter = null;
        $this->projects = $this->getProjects();
    }

    public function newProject(): void
    {
        $this->reset(['editingId']);
        $this->form = [
            'name' => '',
            'description' => '',
            'color' => '#3b82f6',
            'icon' => null,
            'status' => 'active',
            'phase' => 'planning',
            'parent_id' => null,
            'project_category_id' => $this->categoryFilter,
            'start_date' => null,
            'end_date' => null,
            'is_public' => false,
            'sort_order' => 0,
        ];
        $this->showEditor = true;
    }

    public function editProject(int $id): void
    {
        $project = Project::findOrFail($id);
        $this->editingId = $project->id;

        $this->form['name'] = $project->name;
        $this->form['description'] = $project->description ?? '';
        $this->form['color'] = $project->color;
        $this->form['icon'] = $project->icon;
        $this->form['status'] = $project->status;
        $this->form['phase'] = $project->phase;
        $this->form['parent_id'] = $project->parent_id;
        $this->form['project_category_id'] = $project->project_category_id;
        $this->form['start_date'] = $project->start_date?->format('Y-m-d');
        $this->form['end_date'] = $project->end_date?->format('Y-m-d');
        $this->form['is_public'] = $project->is_public;
        $this->form['sort_order'] = $project->sort_order;

        $this->showEditor = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            $project = Project::findOrFail($this->editingId);
            $project->update($this->form);
        } else {
            $project = Project::create($this->form);
        }

        $this->showEditor = false;
        $this->editingId = null;
        $this->projects = $this->getProjects();
        $this->rootProjects = Project::root()->active()->orderBy('sort_order')->get();

        $this->dispatch('toast', type: 'success', title: $this->editingId ? 'Proyecto actualizado' : 'Proyecto creado');
    }

    public function deleteProject(int $id): void
    {
        Project::findOrFail($id)->delete();
        $this->projects = $this->getProjects();
        $this->rootProjects = Project::root()->active()->orderBy('sort_order')->get();
        $this->dispatch('toast', type: 'success', title: 'Proyecto eliminado');
    }

    public function newCategory(): void
    {
        $this->reset(['categoryForm']);
        $this->categoryForm['color'] = '#6366f1';
        $this->showCategoryModal = true;
    }

    public function saveCategory(): void
    {
        $this->validate($this->categoryRules);

        ProjectCategory::create(array_merge($this->categoryForm, [
            'created_by' => auth()->id(),
        ]));
        $this->showCategoryModal = false;
        $this->categories = ProjectCategory::orderBy('sort_order')->get();
        $this->dispatch('toast', type: 'success', title: 'Categoría creada');
    }

    public function render()
    {
        return view('livewire.projects.projects')
            ->layout('layouts.front');
    }
}
