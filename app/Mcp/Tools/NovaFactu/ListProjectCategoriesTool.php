<?php

namespace App\Mcp\Tools\NovaFactu;

use App\Models\ProjectCategory;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Lista categorías de proyectos con filtros opcionales por texto y estado. Devuelve un resumen en JSON de cada categoría.')]
class ListProjectCategoriesTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'search' => 'nullable|string',
            'status' => 'nullable|string|in:active,archived',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $categories = ProjectCategory::query()
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('sort_order')
            ->latest()
            ->limit($validated['limit'] ?? 20)
            ->get();

        return Response::json([
            'count' => $categories->count(),
            'categories' => $categories->map(fn (ProjectCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'color' => $category->color,
                'icon' => $category->icon,
                'status' => $category->status,
                'sort_order' => $category->sort_order,
                'projects_count' => $category->projects_count ?? $category->projects()->count(),
                'created_by' => $category->created_by,
                'created_at' => $category->created_at?->toDateTimeString(),
            ])->values(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Texto a buscar en nombre o descripción de la categoría.'),
            'status' => $schema->string()->description('Filtrar por estado (active, archived).'),
            'limit' => $schema->integer()->description('Número máximo de categorías a devolver (1-100). Por defecto 20.'),
        ];
    }
}
