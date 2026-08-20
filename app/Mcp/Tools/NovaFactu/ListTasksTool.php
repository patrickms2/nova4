<?php

namespace App\Mcp\Tools\NovaFactu;

use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Lista tareas con filtros opcionales por texto, proyecto, categoría, estado, prioridad, tipo y rango de fechas. Devuelve un resumen en JSON de cada tarea.')]
class ListTasksTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'search' => 'nullable|string',
            'project_id' => 'nullable|integer|exists:projects,id',
            'task_category_id' => 'nullable|integer|exists:task_categories,id',
            'status' => 'nullable|string|in:pending,in_progress,completed,cancelled',
            'priority' => 'nullable|string|in:low,medium,high',
            'type' => 'nullable|string|in:general,development,design,documentation,testing,deployment',
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $tasks = Task::query()
            ->with('project:id,name', 'taskCategory:id,name,color', 'assignedTo:id,name')
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->when($validated['project_id'] ?? null, fn ($query, int $projectId) => $query->where('project_id', $projectId))
            ->when($validated['task_category_id'] ?? null, fn ($query, int $categoryId) => $query->where('task_category_id', $categoryId))
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['priority'] ?? null, fn ($query, string $priority) => $query->where('priority', $priority))
            ->when($validated['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->when($validated['fecha_desde'] ?? null, fn ($query, string $desde) => $query->whereDate('due_date', '>=', $desde))
            ->when($validated['fecha_hasta'] ?? null, fn ($query, string $hasta) => $query->whereDate('due_date', '<=', $hasta))
            ->orderBy('sort_order')
            ->latest()
            ->limit($validated['limit'] ?? 20)
            ->get();

        return Response::json([
            'count' => $tasks->count(),
            'tasks' => $tasks->map(fn (Task $task): array => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'priority' => $task->priority,
                'type' => $task->type,
                'due_date' => $task->due_date?->toDateString(),
                'is_completed' => (bool) $task->is_completed,
                'completed_at' => $task->completed_at?->toDateTimeString(),
                'project_id' => $task->project_id,
                'project' => $task->project?->name,
                'task_category_id' => $task->task_category_id,
                'task_category' => $task->taskCategory?->name,
                'task_category_color' => $task->taskCategory?->color,
                'assigned_to' => $task->assigned_to,
                'assigned_to_name' => $task->assignedTo?->name,
                'created_at' => $task->created_at?->toDateTimeString(),
            ])->values(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Texto a buscar en título o descripción de la tarea.'),
            'project_id' => $schema->integer()->description('Filtrar por ID de proyecto.'),
            'task_category_id' => $schema->integer()->description('Filtrar por ID de categoría de tarea.'),
            'status' => $schema->string()->description('Filtrar por estado (pending, in_progress, completed, cancelled).'),
            'priority' => $schema->string()->description('Filtrar por prioridad (low, medium, high).'),
            'type' => $schema->string()->description('Filtrar por tipo (general, development, design, documentation, testing, deployment).'),
            'fecha_desde' => $schema->string()->description('Fecha mínima de vencimiento (Y-m-d).'),
            'fecha_hasta' => $schema->string()->description('Fecha máxima de vencimiento (Y-m-d).'),
            'limit' => $schema->integer()->description('Número máximo de tareas a devolver (1-100). Por defecto 20.'),
        ];
    }
}
