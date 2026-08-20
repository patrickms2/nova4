<?php

namespace App\Mcp\Tools\NovaFactu;

use App\Models\Note;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Crea una nueva nota. Acepta título, contenido en markdown, carpeta, proyecto, etiquetas y fijado. Devuelve la nota creada en JSON.')]
class CreateNoteTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'folder_id' => 'nullable|integer|exists:folders,id',
            'project_id' => 'nullable|integer|exists:projects,id',
            'tags' => 'nullable|array',
            'is_pinned' => 'nullable|boolean',
        ]);

        $note = Note::create(array_merge($validated, [
            'created_by' => auth()->id(),
            'is_pinned' => $validated['is_pinned'] ?? false,
            'pinned_at' => ($validated['is_pinned'] ?? false) ? now() : null,
        ]));

        return Response::json([
            'id' => $note->id,
            'title' => $note->title,
            'content' => $note->content,
            'excerpt' => $note->excerpt,
            'folder_id' => $note->folder_id,
            'project_id' => $note->project_id,
            'tags' => $note->tags ?? [],
            'is_pinned' => (bool) $note->is_pinned,
            'pinned_at' => $note->pinned_at?->toDateTimeString(),
            'created_by' => $note->created_by,
            'created_at' => $note->created_at?->toDateTimeString(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->required()->description('Título de la nota.'),
            'content' => $schema->string()->required()->description('Contenido de la nota en markdown.'),
            'folder_id' => $schema->integer()->description('ID de la carpeta asociada.'),
            'project_id' => $schema->integer()->description('ID del proyecto asociado.'),
            'tags' => $schema->array()->description('Etiquetas de la nota.'),
            'is_pinned' => $schema->boolean()->description('Si la nota está fijada.'),
        ];
    }
}
