<?php

namespace App\Mcp\Tools\NovaFactu;

use App\Models\Note;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Actualiza una nota existente por su ID. Los campos actualizables son título, contenido, carpeta, proyecto, etiquetas y fijado. Devuelve la nota actualizada en JSON.')]
class UpdateNoteTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:notes,id',
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'folder_id' => 'nullable|integer|exists:folders,id',
            'project_id' => 'nullable|integer|exists:projects,id',
            'tags' => 'nullable|array',
            'is_pinned' => 'nullable|boolean',
        ]);

        $note = Note::findOrFail($validated['id']);

        $updateData = array_filter($validated, fn ($value) => $value !== null, ARRAY_FILTER_USE_KEY);

        if (isset($updateData['is_pinned'])) {
            $updateData['pinned_at'] = $updateData['is_pinned'] ? now() : null;
        }

        $note->update($updateData);

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
            'updated_at' => $note->updated_at?->toDateTimeString(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->required()->description('ID de la nota a actualizar.'),
            'title' => $schema->string()->description('Título de la nota.'),
            'content' => $schema->string()->description('Contenido de la nota en markdown.'),
            'folder_id' => $schema->integer()->description('ID de la carpeta asociada.'),
            'project_id' => $schema->integer()->description('ID del proyecto asociado.'),
            'tags' => $schema->array()->description('Etiquetas de la nota.'),
            'is_pinned' => $schema->boolean()->description('Si la nota está fijada.'),
        ];
    }
}
