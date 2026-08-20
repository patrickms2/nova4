<?php

namespace App\Livewire\Tasks;

use App\Models\Task;
use App\Models\Project;
use App\Models\TaskCategory;
use Livewire\Component;
use Livewire\WithPagination;

class Tasks extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $projectFilter = null;
    public ?string $statusFilter = null;
    public ?string $priorityFilter = null;
    public ?string $typeFilter = null;

    public bool $showEditor = false;
    public bool $showCategoryModal = false;
    public ?int $editingId = null;

    public array $form = [
        'title' => '',
        'description' => '',
        'status' => 'pending',
        'priority' => 'medium',
        'type' => 'general',
        'due_date' => null,
        'project_id' => null,
        'task_category_id' => null,
    ];

    public array $categoryForm = [
        'name' => '',
        'slug' => '',
        'description' => '',
        'color' => '#6366f1',
        'icon' => null,
        'status' => 'active',
        'sort_order' => 0,
    ];

    public array $projects = [];
    public array $taskCategories = [];
    public $tasks = [];

    protected $rules = [
        'form.title' => 'required|string|max:255',
        'form.description' => 'nullable|string',
        'form.status' => 'required|in:pending,in_progress,completed,cancelled',
        'form.priority' => 'required|in:low,medium,high',
        'form.type' => 'required|in:general,development,design,documentation,testing,deployment',
        'form.due_date' => 'nullable|date',
        'form.project_id' => 'nullable|exists:projects,id',
        'form.task_category_id' => 'nullable|exists:task_categories,id',
    ];

    protected $categoryRules = [
        'categoryForm.name' => 'required|string|max:255',
        'categoryForm.slug' => 'required|string|max:255|unique:task_categories,slug',
        'categoryForm.color' => 'required|string',
        'categoryForm.status' => 'required|in:active,archived',
    ];

    public function mount(): void
    {
        $this->init();
    }

    public function init(): void
    {
        $this->tasks = $this->getTasks();
        $this->projects = Project::active()->get()->toArray();
        $this->taskCategories = TaskCategory::active()->orderBy('sort_order')->get()->toArray();
    }

    public function getTasks()
    {
        return Task::query()
            ->with('project', 'assignedTo')
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%")
                ->orWhere('description', 'like', "%{$this->search}%")
            )
            ->when($this->projectFilter, fn ($q) => $q->where('project_id', $this->projectFilter))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->priorityFilter, fn ($q) => $q->where('priority', $this->priorityFilter))
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->orderBy('sort_order')
            ->latest()
            ->get();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->projectFilter = null;
        $this->statusFilter = null;
        $this->priorityFilter = null;
        $this->typeFilter = null;
        $this->tasks = $this->getTasks();
    }

    public function newTask(): void
    {
        $this->reset(['editingId']);
        $this->form = [
            'title' => '',
            'description' => '',
            'status' => 'pending',
            'priority' => 'medium',
            'type' => 'general',
            'due_date' => null,
            'project_id' => $this->projectFilter,
            'task_category_id' => null,
        ];
        $this->showEditor = true;
    }

    public function editTask(int $id): void
    {
        $task = Task::findOrFail($id);
        $this->editingId = $task->id;

        $this->form['title'] = $task->title;
        $this->form['description'] = $task->description ?? '';
        $this->form['status'] = $task->status;
        $this->form['priority'] = $task->priority;
        $this->form['type'] = $task->type ?? 'general';
        $this->form['due_date'] = $task->due_date?->format('Y-m-d');
        $this->form['project_id'] = $task->project_id;
        $this->form['task_category_id'] = $task->task_category_id;

        $this->showEditor = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            $task = Task::findOrFail($this->editingId);
            $task->update($this->form);

            if ($this->form['status'] === 'completed' && !$task->is_completed) {
                $task->markAsCompleted();
            }

            // Si la tarea está sincronizada con ClickUp, actualizarla también allí
            if ($task->clickup_task_id) {
                try {
                    $this->updateTaskInClickUp($task);
                } catch (\Exception $e) {
                    // No fallar el guardado local si falla la actualización en ClickUp
                    $this->dispatch('toast', type: 'warning', title: 'Tarea guardada localmente, pero error al actualizar en ClickUp: ' . $e->getMessage());
                }
            }
        } else {
            $task = Task::create($this->form);
        }

        $this->showEditor = false;
        $this->editingId = null;
        $this->tasks = $this->getTasks();

        $this->dispatch('toast', type: 'success', title: $this->editingId ? 'Tarea actualizada' : 'Tarea creada');
    }

    public function deleteTask(int $id): void
    {
        Task::findOrFail($id)->delete();
        $this->tasks = $this->getTasks();
        $this->dispatch('toast', type: 'success', title: 'Tarea eliminada');
    }

    public function newCategory(): void
    {
        $this->reset(['categoryForm']);
        $this->categoryForm['color'] = '#6366f1';
        $this->categoryForm['status'] = 'active';
        $this->showCategoryModal = true;
    }

    public function saveCategory(): void
    {
        $this->validate($this->categoryRules);

        TaskCategory::create(array_merge($this->categoryForm, [
            'created_by' => auth()->id(),
        ]));
        $this->showCategoryModal = false;
        $this->taskCategories = TaskCategory::active()->orderBy('sort_order')->get()->toArray();
        $this->dispatch('toast', type: 'success', title: 'Categoría creada');
    }

    public function toggleComplete(int $id): void
    {
        $task = Task::findOrFail($id);
        if ($task->is_completed) {
            $task->markAsPending();
        } else {
            $task->markAsCompleted();
        }
        $this->tasks = $this->getTasks();
    }

    public function updateStatus(int $id, string $status): void
    {
        $task = Task::findOrFail($id);
        $task->update(['status' => $status]);

        if ($status === 'completed') {
            $task->markAsCompleted();
        }

        $this->tasks = $this->getTasks();
    }

    public function exportToClickUp(int $id): void
    {
        $task = Task::findOrFail($id);

        try {
            $clickupTaskId = $this->syncTaskToClickUp($task);

            $task->update(['clickup_task_id' => $clickupTaskId]);
            $this->tasks = $this->getTasks();

            $this->dispatch('toast', type: 'success', title: 'Tarea exportada a ClickUp');
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', title: 'Error al exportar: ' . $e->getMessage());
        }
    }

    public function importFromClickUp(): void
    {
        try {
            $importedCount = $this->syncTasksFromClickUp();
            $this->tasks = $this->getTasks();

            $this->dispatch('toast', type: 'success', title: "{$importedCount} tareas importadas de ClickUp");
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', title: 'Error al importar: ' . $e->getMessage());
        }
    }

    private function syncTaskToClickUp(Task $task): string
    {
        $clickupApiToken = config('services.clickup.api_token');
        $clickupListId = config('services.clickup.list_id');

        if (!$clickupApiToken || !$clickupListId) {
            throw new \Exception('Configuración de ClickUp no encontrada');
        }

        $client = new \GuzzleHttp\Client();

        $response = $client->post("https://api.clickup.com/api/v2/list/{$clickupListId}/task", [
            'headers' => [
                'Authorization' => $clickupApiToken,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'name' => $task->title,
                'description' => $task->description,
                'status' => $this->mapStatusToClickUp($task->status),
                'priority' => $this->mapPriorityToClickUp($task->priority),
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['id'];
    }

    private function syncTasksFromClickUp(): int
    {
        $clickupApiToken = config('services.clickup.api_token');
        $clickupListId = config('services.clickup.list_id');

        if (!$clickupApiToken || !$clickupListId) {
            throw new \Exception('Configuración de ClickUp no encontrada');
        }

        $client = new \GuzzleHttp\Client();

        $response = $client->get("https://api.clickup.com/api/v2/list/{$clickupListId}/task", [
            'headers' => [
                'Authorization' => $clickupApiToken,
            ],
        ]);
        $data = json_decode($response->getBody(), true);
        $importedCount = 0;

        foreach ($data['tasks'] as $clickupTask) {
            $existingTask = Task::where('clickup_task_id', $clickupTask['id'])->first();

            if (!$existingTask) {
                Task::create([
                    'title' => $clickupTask['name'],
                    'description' => $clickupTask['description'] ?? '',
                    'status' => $this->mapStatusFromClickUp($clickupTask['status']['status']),
                    'priority' => $this->mapPriorityFromClickUp($clickupTask['priority']['priority']),
                    'clickup_task_id' => $clickupTask['id'],
                    'created_by' => auth()->id(),
                ]);
                $importedCount++;
            }
        }

        return $importedCount;
    }

    private function mapStatusToClickUp(string $status): string
    {
        return match($status) {
            'pending' => 'to do',
            'in_progress' => 'in progress',
            'completed' => 'complete',
            default => 'to do',
        };
    }

    private function mapStatusFromClickUp(string $status): string
    {
        return match($status) {
            'to do' => 'pending',
            'in progress' => 'in_progress',
            'complete' => 'completed',
            default => 'pending',
        };
    }

    private function mapPriorityToClickUp(string $priority): string
    {
        return match($priority) {
            'high' => 'urgent',
            'medium' => 'normal',
            'low' => 'low',
            default => 'normal',
        };
    }

    private function mapPriorityFromClickUp(string $priority): string
    {
        return match($priority) {
            'urgent' => 'high',
            'high' => 'high',
            'normal' => 'medium',
            'low' => 'low',
            default => 'medium',
        };
    }

    private function updateTaskInClickUp(Task $task): void
    {
        $clickupApiToken = config('services.clickup.api_token');
        $clickupTaskId = $task->clickup_task_id;

        if (!$clickupApiToken || !$clickupTaskId) {
            throw new \Exception('Configuración de ClickUp no encontrada o tarea no sincronizada');
        }

        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->put("https://api.clickup.com/api/v2/task/{$clickupTaskId}", [
                'headers' => [
                    'Authorization' => $clickupApiToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'name' => $task->title,
                    'description' => $task->description,
                    'status' => $this->mapStatusToClickUp($task->status),
                    'priority' => $this->mapPriorityToClickUp($task->priority),
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Error en la respuesta de ClickUp');
            }
        } catch (\Exception $e) {
            throw new \Exception('Error al actualizar tarea en ClickUp: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.tasks.tasks')
            ->layout('layouts.front');
    }
}
