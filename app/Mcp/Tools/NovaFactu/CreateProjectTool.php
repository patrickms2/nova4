<?php

namespace App\Mcp\Tools\NovaFactu;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Crea un nuevo proyecto. Acepta nombre, descripción, categoría, fase, fechas, color, icono, visibilidad y ordenamiento. Devuelve el proyecto creado en JSON.')]
class CreateProjectTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_category_id' => 'nullable|integer|exists:project_categories,id',
            'phase' => 'nullable|string|in:planning,development,testing,deployment,completed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'color' => 'nullable|string',
            'icon' => 'nullable|string',
            'is_public' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $project = Project::create(array_merge($validated, [
            'created_by' => auth()->id(),
            'status' => 'active',
        ]));

        return Response::json([
            'id' => $project->id,
            'name' => $project->name,
            'description' => $project->description,
            'phase' => $project->phase,
            'status' => $project->status,
            'start_date' => $project->start_date?->toDateString(),
            'end_date' => $project->end_date?->toDateString(),
            'color' => $project->color,
            'icon' => $project->icon,
            'is_public' => (bool) $project->is_public,
            'project_category_id' => $project->project_category_id,
            'created_by' => $project->created_by,
            'created_at' => $project->created_at?->toDateTimeString(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required()->description('Nombre del proyecto.'),
            'description' => $schema->string()->description('Descripción del proyecto.'),
            'project_category_id' => $schema->integer()->description('ID de categoría de proyecto.'),
            'phase' => $schema->string()->description('Fase del proyecto (planning, development, testing, deployment, completed).'),
            'start_date' => $schema->string()->description('Fecha de inicio (Y-m-d).'),
            'end_date' => $schema->string()->description('Fecha de fin (Y-m-d).'),
            'color' => $schema->string()->description('Color del proyecto (hex).'),
            'icon' => $schema->string()->description('Icono del proyecto.'),
            'is_public' => $schema->boolean()->description('Si el proyecto es público.'),
            'sort_order' => $schema->integer()->description('Ordenamiento del proyecto.'),
        ];
    }
}
