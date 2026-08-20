<?php

namespace App\Mcp\Tools\NovaFactu;

use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Crea una nueva tarea. Acepta título, descripción, estado, prioridad, tipo, fecha límite, proyecto, categoría y asignación. Devuelve la tarea creada en JSON.')]
class CreateTaskTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:pending,in_progress,completed,cancelled',
            'priority' => 'nullable|string|in:low,medium,high',
            'type' => 'nullable|string|in:general,development,design,documentation,testing,deployment',
            'due_date' => 'nullable|date',
            'project_id' => 'nullable|integer|exists:projects,id',
            'task_category_id' => 'nullable|integer|exists:task_categories,id',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'sort_order' => 'nullable|integer',
        ]);

        $task = Task::create(array_merge($validated, [
            'is_completed' => ($validated['status'] ?? 'pending') === 'completed',
            'completed_at' => ($validated['status'] ?? 'pending') === 'completed' ? now() : null,
        ]));

        return Response::json([
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
            'task_category_id' => $task->task_category_id,
            'assigned_to' => $task->assigned_to,
            'created_at' => $task->created_at?->toDateTimeString(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->required()->description('Título de la tarea.'),
            'description' => $schema->string()->description('Descripción de la tarea.'),
            'status' => $schema->string()->description('Estado de la tarea (pending, in_progress, completed, cancelled).'),
            'priority' => $schema->string()->description('Prioridad de la tarea (low, medium, high).'),
            'type' => $schema->string()->description('Tipo de tarea (general, development, design, documentation, testing, deployment).'),
            'due_date' => $schema->string()->description('Fecha límite (Y-m-d).'),
            'project_id' => $schema->integer()->description('ID del proyecto asociado.'),
            'task_category_id' => $schema->integer()->description('ID de categoría de tarea.'),
            'assigned_to' => $schema->integer()->description('ID del usuario asignado.'),
            'sort_order' => $schema->integer()->description('Ordenamiento de la tarea.'),
        ];
    }
}
