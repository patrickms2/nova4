<?php

namespace App\Mcp\Tools\ClickUp;

use App\Services\ClickUp\ClickUpService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Crea una tarea en ClickUp. Acepta nombre, descripción, prioridad y lista opcional. Devuelve la tarea creada en JSON.')]
class CreateTaskTool extends Tool
{
    public function __construct(protected ?ClickUpService $service = null)
    {
        $this->service = $this->service ?? app(ClickUpService::class);
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'list_id' => 'nullable|string',
            'priority' => 'nullable|integer',
            'assignees' => 'nullable|array',
            'status' => 'nullable|string',
        ]);

        $payload = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
        ];

        if (! empty($validated['list_id'])) {
            $payload['list_id'] = $validated['list_id'];
        }

        if (! empty($validated['priority'])) {
            $payload['priority'] = $validated['priority'];
        }

        if (! empty($validated['assignees'])) {
            $payload['assignees'] = $validated['assignees'];
        }

        if (! empty($validated['status'])) {
            $payload['status'] = $validated['status'];
        }

        $result = $this->service->createTask($payload);

        return Response::json($result);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Nombre de la tarea en ClickUp.'),
            'description' => $schema->string()->description('Descripción de la tarea.'),
            'list_id' => $schema->string()->description('ID de la lista de ClickUp. Si no se indica, se usa la configuración por defecto.'),
            'priority' => $schema->integer()->description('Prioridad de la tarea.'),
            'assignees' => $schema->array()->description('IDs de usuarios a asignar.'),
            'status' => $schema->string()->description('Estado inicial de la tarea.'),
        ];
    }
}
