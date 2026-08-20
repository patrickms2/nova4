<?php

namespace App\Mcp\Tools\NovaFactu;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Elimina un proyecto por su ID. Requiere confirmación explícita para evitar eliminaciones accidentales. Devuelve el resultado de la eliminación en JSON.')]
class DeleteProjectTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:projects,id',
            'confirmado' => 'required|boolean',
        ]);

        if (!$validated['confirmado']) {
            return Response::error('Se requiere confirmación explícita para eliminar el proyecto.');
        }

        $project = Project::findOrFail($validated['id']);
        $projectId = $project->id;
        $projectName = $project->name;
        $project->delete();

        return Response::json([
            'success' => true,
            'message' => "Proyecto '{$projectName}' (ID: {$projectId}) eliminado correctamente.",
            'deleted_id' => $projectId,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->required()->description('ID del proyecto a eliminar.'),
            'confirmado' => $schema->boolean()->required()->description('Confirmación explícita de eliminación.'),
        ];
    }
}
