<?php

namespace App\Mcp\Tools\NovaFactu;

use App\Models\Note;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Lista notas con filtros opcionales por texto, carpeta, proyecto, etiquetas, fijado y rango de fechas. Devuelve un resumen en JSON de cada nota.')]
class ListNotesTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'search' => 'nullable|string',
            'folder_id' => 'nullable|integer|exists:folders,id',
            'project_id' => 'nullable|integer|exists:projects,id',
            'is_pinned' => 'nullable|boolean',
            'tag' => 'nullable|string',
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $notes = Note::query()
            ->with('folder:id,name,color', 'project:id,name')
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            })
            ->when($validated['folder_id'] ?? null, fn ($query, int $folderId) => $query->where('folder_id', $folderId))
            ->when($validated['project_id'] ?? null, fn ($query, int $projectId) => $query->where('project_id', $projectId))
            ->when($validated['is_pinned'] ?? null, fn ($query, bool $isPinned) => $query->where('is_pinned', $isPinned))
            ->when($validated['tag'] ?? null, fn ($query, string $tag) => $query->whereJsonContains('tags', $tag))
            ->when($validated['fecha_desde'] ?? null, fn ($query, string $desde) => $query->whereDate('created_at', '>=', $desde))
            ->when($validated['fecha_hasta'] ?? null, fn ($query, string $hasta) => $query->whereDate('created_at', '<=', $hasta))
            ->orderBy('is_pinned', 'desc')
            ->orderBy('pinned_at', 'desc')
            ->latest()
            ->limit($validated['limit'] ?? 20)
            ->get();

        return Response::json([
            'count' => $notes->count(),
            'notes' => $notes->map(fn (Note $note): array => [
                'id' => $note->id,
                'title' => $note->title,
                'content' => $note->content,
                'excerpt' => $note->excerpt,
                'folder_id' => $note->folder_id,
                'folder' => $note->folder?->name,
                'folder_color' => $note->folder?->color,
                'project_id' => $note->project_id,
                'project' => $note->project?->name,
                'tags' => $note->tags ?? [],
                'is_pinned' => (bool) $note->is_pinned,
                'pinned_at' => $note->pinned_at?->toDateTimeString(),
                'created_by' => $note->created_by,
                'created_at' => $note->created_at?->toDateTimeString(),
                'updated_at' => $note->updated_at?->toDateTimeString(),
            ])->values(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Texto a buscar en título o contenido de la nota.'),
            'folder_id' => $schema->integer()->description('Filtrar por ID de carpeta.'),
            'project_id' => $schema->integer()->description('Filtrar por ID de proyecto.'),
            'is_pinned' => $schema->boolean()->description('Filtrar por notas fijadas.'),
            'tag' => $schema->string()->description('Filtrar por etiqueta específica.'),
            'fecha_desde' => $schema->string()->description('Fecha mínima de creación (Y-m-d).'),
            'fecha_hasta' => $schema->string()->description('Fecha máxima de creación (Y-m-d).'),
            'limit' => $schema->integer()->description('Número máximo de notas a devolver (1-100). Por defecto 20.'),
        ];
    }
}
