<?php

namespace App\Mcp\Tools\NovaFactu;

use App\Models\TaskCategory;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Crea una nueva categoría de tarea. Acepta nombre, slug, descripción, color, icono, estado y ordenamiento. Devuelve la categoría creada en JSON.')]
class CreateTaskCategoryTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:task_categories,slug',
            'description' => 'nullable|string',
            'color' => 'nullable|string',
            'icon' => 'nullable|string',
            'status' => 'nullable|string|in:active,archived',
            'sort_order' => 'nullable|integer',
        ]);

        $category = TaskCategory::create(array_merge($validated, [
            'created_by' => auth()->id(),
        ]));

        return Response::json([
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'color' => $category->color,
            'icon' => $category->icon,
            'status' => $category->status,
            'sort_order' => $category->sort_order,
            'created_by' => $category->created_by,
            'created_at' => $category->created_at?->toDateTimeString(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required()->description('Nombre de la categoría.'),
            'slug' => $schema->string()->required()->description('Slug único de la categoría.'),
            'description' => $schema->string()->description('Descripción de la categoría.'),
            'color' => $schema->string()->description('Color de la categoría (hex).'),
            'icon' => $schema->string()->description('Icono de la categoría.'),
            'status' => $schema->string()->description('Estado de la categoría (active, archived).'),
            'sort_order' => $schema->integer()->description('Ordenamiento de la categoría.'),
        ];
    }
}
