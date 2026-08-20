<?php

namespace App\Mcp\Tools\ClickUp;

use App\Services\ClickUp\ClickUpService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Lista tareas de una lista de ClickUp. Devuelve el listado de tareas en JSON.')]
class ListTasksTool extends Tool
{
    public function __construct(protected ?ClickUpService $service = null)
    {
        $this->service = $this->service ?? app(ClickUpService::class);
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'list_id' => 'nullable|string',
        ]);

        $tasks = $this->service->getTasks($validated['list_id'] ?? null);

        return Response::json([
            'tasks' => $tasks,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'list_id' => $schema->string()->description('ID de la lista de ClickUp. Si no se indica, se usa la configuración por defecto.'),
        ];
    }
}
