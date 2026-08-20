<?php

namespace App\Mcp\Tools\NovaFactu;

use App\Models\Note;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Elimina una nota por su ID. Requiere confirmación explícita para evitar eliminaciones accidentales. Devuelve el resultado de la eliminación en JSON.')]
class DeleteNoteTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:notes,id',
            'confirmado' => 'required|boolean',
        ]);

        if (!$validated['confirmado']) {
            return Response::error('Se requiere confirmación explícita para eliminar la nota.');
        }

        $note = Note::findOrFail($validated['id']);
        $noteId = $note->id;
        $noteTitle = $note->title;
        $note->delete();

        return Response::json([
            'success' => true,
            'message' => "Nota '{$noteTitle}' (ID: {$noteId}) eliminada correctamente.",
            'deleted_id' => $noteId,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->required()->description('ID de la nota a eliminar.'),
            'confirmado' => $schema->boolean()->required()->description('Confirmación explícita de eliminación.'),
        ];
    }
}
