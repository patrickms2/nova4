<?php

namespace App\Mcp\Tools\NovaFactu;

use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Exporta una tarea de NovaFact a ClickUp. Convierte la tarea local a formato ClickUp y la crea en la lista especificada. Devuelve la tarea creada en ClickUp en JSON.')]
class ExportTaskToClickUpTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'task_id' => 'required|integer|exists:tasks,id',
            'clickup_list_id' => 'nullable|string',
        ]);

        $task = Task::with('project', 'taskCategory')->findOrFail($validated['task_id']);

        // Mapeo de prioridades
        $priorityMap = [
            'low' => 1,
            'medium' => 2,
            'high' => 3,
        ];

        // Mapeo de estados
        $statusMap = [
            'pending' => 'to do',
            'in_progress' => 'in progress',
            'completed' => 'done',
            'cancelled' => 'cancelled',
        ];

        // Construir descripción con contexto
        $description = $task->description ?? '';
        if ($task->project) {
            $description .= "\n\n**Proyecto:** {$task->project->name}";
        }
        if ($task->taskCategory) {
            $description .= "\n**Categoría:** {$task->taskCategory->name}";
        }
        if ($task->due_date) {
            $description .= "\n**Fecha límite:** {$task->due_date->toDateString()}";
        }
        if ($task->type && $task->type !== 'general') {
            $description .= "\n**Tipo:** {$task->type}";
        }

        // Llamar a la tool de ClickUp para crear la tarea
        $clickupResult = $this->callClickUpCreateTask([
            'name' => $task->title,
            'description' => $description,
            'list_id' => $validated['clickup_list_id'] ?? null,
            'priority' => $priorityMap[$task->priority] ?? 2,
            'status' => $statusMap[$task->status] ?? 'to do',
        ]);

        if (!$clickupResult['success']) {
            return Response::error('Error al crear tarea en ClickUp: ' . $clickupResult['error'] ?? 'Unknown error');
        }

        // Guardar referencia de ClickUp en la tarea local
        $task->update([
            'clickup_task_id' => $clickupResult['task']['id'] ?? null,
        ]);

        return Response::json([
            'success' => true,
            'message' => 'Tarea exportada a ClickUp correctamente',
            'local_task_id' => $task->id,
            'clickup_task_id' => $clickupResult['task']['id'] ?? null,
            'clickup_task' => $clickupResult['task'] ?? null,
        ]);
    }

    protected function callClickUpCreateTask(array $data): array
    {
        try {
            $result = \Laravel\Mcp\Facades\Mcp::call('clickup', 'CreateTaskTool', $data);
            return [
                'success' => true,
                'task' => $result,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->integer()->required()->description('ID de la tarea local a exportar.'),
            'clickup_list_id' => $schema->string()->description('ID de la lista de ClickUp destino. Si no se indica, se usa la configuración por defecto.'),
        ];
    }
}
