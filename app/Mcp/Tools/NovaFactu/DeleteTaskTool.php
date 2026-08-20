<?php

namespace App\Mcp\Tools\NovaFactu;

use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Elimina una tarea por su ID. Requiere confirmación explícita para evitar eliminaciones accidentales. Devuelve el resultado de la eliminación en JSON.')]
class DeleteTaskTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:tasks,id',
            'confirmado' => 'required|boolean',
        ]);

        if (!$validated['confirmado']) {
            return Response::error('Se requiere confirmación explícita para eliminar la tarea.');
        }

        $task = Task::findOrFail($validated['id']);
        $taskId = $task->id;
        $taskTitle = $task->title;
        $task->delete();

        return Response::json([
            'success' => true,
            'message' => "Tarea '{$taskTitle}' (ID: {$taskId}) eliminada correctamente.",
            'deleted_id' => $taskId,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->required()->description('ID de la tarea a eliminar.'),
            'confirmado' => $schema->boolean()->required()->description('Confirmación explícita de eliminación.'),
        ];
    }
}
