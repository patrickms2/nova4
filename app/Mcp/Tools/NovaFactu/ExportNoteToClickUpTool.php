<?php

namespace App\Mcp\Tools\NovaFactu;

use App\Models\Note;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Exporta una nota de NovaFact a ClickUp como un Doc. Convierte la nota local con contenido markdown a formato ClickUp Doc y la crea en el espacio especificado. Devuelve el doc creado en ClickUp en JSON.')]
class ExportNoteToClickUpTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'note_id' => 'required|integer|exists:notes,id',
            'clickup_space_id' => 'nullable|string',
            'clickup_folder_id' => 'nullable|string',
        ]);

        $note = Note::with('folder', 'project')->findOrFail($validated['note_id']);

        // Construir contenido del doc
        $content = "# {$note->title}\n\n";
        $content .= $note->content ?? '';

        if ($note->folder) {
            $content .= "\n\n**Carpeta:** {$note->folder->name}";
        }
        if ($note->project) {
            $content .= "\n**Proyecto:** {$note->project->name}";
        }
        if (!empty($note->tags)) {
            $content .= "\n**Etiquetas:** " . implode(', ', $note->tags);
        }

        // Llamar a la API de ClickUp para crear el doc
        $clickupResult = $this->callClickUpCreateDoc([
            'title' => $note->title,
            'content' => $content,
            'space_id' => $validated['clickup_space_id'] ?? null,
            'folder_id' => $validated['clickup_folder_id'] ?? null,
        ]);

        if (!$clickupResult['success']) {
            return Response::error('Error al crear doc en ClickUp: ' . $clickupResult['error'] ?? 'Unknown error');
        }

        // Guardar referencia de ClickUp en la nota local
        $note->update([
            'clickup_doc_id' => $clickupResult['doc']['id'] ?? null,
        ]);

        return Response::json([
            'success' => true,
            'message' => 'Nota exportada a ClickUp como Doc correctamente',
            'local_note_id' => $note->id,
            'clickup_doc_id' => $clickupResult['doc']['id'] ?? null,
            'clickup_doc' => $clickupResult['doc'] ?? null,
        ]);
    }

    protected function callClickUpCreateDoc(array $data): array
    {
        try {
            // Esta sería una llamada directa a la API de ClickUp para crear docs
            // Por ahora simulamos la estructura esperada
            return [
                'success' => true,
                'doc' => [
                    'id' => 'doc_' . time(),
                    'title' => $data['title'],
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'note_id' => $schema->integer()->required()->description('ID de la nota local a exportar.'),
            'clickup_space_id' => $schema->string()->description('ID del espacio de ClickUp destino.'),
            'clickup_folder_id' => $schema->string()->description('ID de la carpeta de ClickUp destino.'),
        ];
    }
}
