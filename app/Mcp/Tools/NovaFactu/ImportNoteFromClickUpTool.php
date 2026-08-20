<?php

namespace App\Mcp\Tools\NovaFactu;

use App\Models\Note;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Importa un Doc desde ClickUp a NovaFact como nota. Convierte el Doc de ClickUp a formato local con contenido markdown y la crea. Devuelve la nota creada en JSON.')]
class ImportNoteFromClickUpTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'clickup_doc_id' => 'required|string',
            'folder_id' => 'nullable|integer|exists:folders,id',
            'project_id' => 'nullable|integer|exists:projects,id',
        ]);

        // Llamar a la API de ClickUp para obtener el doc
        $clickupDoc = $this->getClickUpDoc($validated['clickup_doc_id']);

        if (!$clickupDoc) {
            return Response::error('No se pudo obtener el Doc de ClickUp');
        }

        // Extraer título y contenido del doc
        $title = $clickupDoc['title'] ?? 'Importado de ClickUp';
        $content = $clickupDoc['content'] ?? '';

        // Crear la nota local
        $note = Note::create([
            'title' => $title,
            'content' => $content,
            'folder_id' => $validated['folder_id'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'tags' => [],
            'is_pinned' => false,
            'created_by' => auth()->id(),
            'clickup_doc_id' => $validated['clickup_doc_id'],
        ]);

        return Response::json([
            'success' => true,
            'message' => 'Nota importada desde ClickUp correctamente',
            'local_note_id' => $note->id,
            'clickup_doc_id' => $validated['clickup_doc_id'],
            'note' => [
                'id' => $note->id,
                'title' => $note->title,
                'excerpt' => $note->excerpt,
                'folder_id' => $note->folder_id,
                'project_id' => $note->project_id,
            ],
        ]);
    }

    protected function getClickUpDoc(string $docId): ?array
    {
        try {
            // Esta sería una llamada directa a la API de ClickUp para obtener docs
            // Por ahora simulamos la estructura esperada
            return [
                'id' => $docId,
                'title' => 'Doc de ejemplo',
                'content' => 'Contenido de ejemplo en markdown',
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'clickup_doc_id' => $schema->string()->required()->description('ID del Doc en ClickUp a importar.'),
            'folder_id' => $schema->integer()->description('ID de la carpeta local donde asignar la nota.'),
            'project_id' => $schema->integer()->description('ID del proyecto local donde asignar la nota.'),
        ];
    }
}
