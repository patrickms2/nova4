<?php

namespace App\Mcp\Tools\NovaFactu;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Lista proyectos con filtros opcionales por texto, categoría, fase, estado y rango de fechas. Devuelve un resumen en JSON de cada proyecto.')]
class ListProjectsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'search' => 'nullable|string',
            'category_id' => 'nullable|integer|exists:project_categories,id',
            'phase' => 'nullable|string|in:planning,development,testing,deployment,completed',
            'status' => 'nullable|string|in:active,archived',
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $projects = Project::query()
            ->with('category:id,name,color')
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->when($validated['category_id'] ?? null, fn ($query, int $categoryId) => $query->where('project_category_id', $categoryId))
            ->when($validated['phase'] ?? null, fn ($query, string $phase) => $query->where('phase', $phase))
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['fecha_desde'] ?? null, fn ($query, string $desde) => $query->whereDate('start_date', '>=', $desde))
            ->when($validated['fecha_hasta'] ?? null, fn ($query, string $hasta) => $query->whereDate('end_date', '<=', $hasta))
            ->orderBy('sort_order')
            ->latest()
            ->limit($validated['limit'] ?? 20)
            ->get();

        return Response::json([
            'count' => $projects->count(),
            'projects' => $projects->map(fn (Project $project): array => [
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
                'category_id' => $project->project_category_id,
                'category' => $project->category?->name,
                'category_color' => $project->category?->color,
                'created_by' => $project->created_by,
                'created_at' => $project->created_at?->toDateTimeString(),
            ])->values(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Texto a buscar en nombre o descripción del proyecto.'),
            'category_id' => $schema->integer()->description('Filtrar por ID de categoría de proyecto.'),
            'phase' => $schema->string()->description('Filtrar por fase (planning, development, testing, deployment, completed).'),
            'status' => $schema->string()->description('Filtrar por estado (active, archived).'),
            'fecha_desde' => $schema->string()->description('Fecha mínima de inicio (Y-m-d).'),
            'fecha_hasta' => $schema->string()->description('Fecha máxima de fin (Y-m-d).'),
            'limit' => $schema->integer()->description('Número máximo de proyectos a devolver (1-100). Por defecto 20.'),
        ];
    }
}
