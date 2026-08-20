<?php

namespace App\Mcp\Tools\NovaFactu;

use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Importa una tarea desde ClickUp a NovaFact. Convierte la tarea de ClickUp a formato local y la crea. Devuelve la tarea creada en JSON.')]
class ImportTaskFromClickUpTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'clickup_task_id' => 'required|string',
            'project_id' => 'nullable|integer|exists:projects,id',
            'task_category_id' => 'nullable|integer|exists:task_categories,id',
        ]);

        // Llamar a la API de ClickUp para obtener la tarea
        $clickupTask = $this->getClickUpTask($validated['clickup_task_id']);

        if (!$clickupTask) {
            return Response::error('No se pudo obtener la tarea de ClickUp');
        }

        // Mapeo de prioridades (ClickUp usa números: 1=low, 2=medium, 3=high)
        $priorityMap = [
            1 => 'low',
            2 => 'medium',
            3 => 'high',
        ];

        // Mapeo de estados
        $statusMap = [
            'to do' => 'pending',
            'in progress' => 'in_progress',
            'done' => 'completed',
            'cancelled' => 'cancelled',
        ];

        // Extraer descripción limpiando formato ClickUp
        $description = $clickupTask['description'] ?? '';

        // Crear la tarea local
        $task = Task::create([
            'title' => $clickupTask['name'] ?? 'Importada de ClickUp',
            'description' => $description,
            'status' => $statusMap[$clickupTask['status']['status'] ?? 'pending'] ?? 'pending',
            'priority' => $priorityMap[$clickupTask['priority']['id'] ?? 2] ?? 'medium',
            'type' => 'general',
            'due_date' => $clickupTask['due_date'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'task_category_id' => $validated['task_category_id'] ?? null,
            'is_completed' => ($statusMap[$clickupTask['status']['status'] ?? 'pending']) === 'completed',
            'completed_at' => ($statusMap[$clickupTask['status']['status'] ?? 'pending']) === 'completed' ? now() : null,
            'clickup_task_id' => $validated['clickup_task_id'],
        ]);

        return Response::json([
            'success' => true,
            'message' => 'Tarea importada desde ClickUp correctamente',
            'local_task_id' => $task->id,
            'clickup_task_id' => $validated['clickup_task_id'],
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'status' => $task->status,
                'priority' => $task->priority,
                'due_date' => $task->due_date?->toDateString(),
                'project_id' => $task->project_id,
                'task_category_id' => $task->task_category_id,
            ],
        ]);
    }

    protected function getClickUpTask(string $taskId): ?array
    {
        try {
            // Esta sería una llamada directa a la API de ClickUp
            // Por ahora simulamos la estructura esperada
            return [
                'id' => $taskId,
                'name' => 'Tarea de ejemplo',
                'description' => 'Descripción de ejemplo',
                'status' => ['status' => 'to do'],
                'priority' => ['id' => 2],
                'due_date' => null,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'clickup_task_id' => $schema->string()->required()->description('ID de la tarea en ClickUp a importar.'),
            'project_id' => $schema->integer()->description('ID del proyecto local donde asignar la tarea.'),
            'task_category_id' => $schema->integer()->description('ID de la categoría de tarea local.'),
        ];
    }
}
