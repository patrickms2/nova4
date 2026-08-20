<?php

namespace App\Mcp\Tools\CasaElPatio;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Lista tareas de Casa El Patio con filtros por estado, prioridad, fecha de vencimiento y búsqueda por título. Devuelve un resumen en JSON.')]
class ListTasksTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'search' => 'nullable|string',
            'status' => 'nullable|string',
            'priority' => 'nullable|string',
            'is_completed' => 'nullable|boolean',
            'due_date_desde' => 'nullable|date',
            'due_date_hasta' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $tareas = Task::query()
            ->with(['assignedTo:id,name'])
            ->when($validated['search'] ?? null, fn ($query, string $search) => $query->where('title', 'like', "%{$search}%"))
            ->when($validated['status'] ?? null, function ($query, string $status): void {
                $statuses = collect(TaskStatus::cases())->map(fn ($s) => $s->value)->all();
                if (in_array($status, $statuses, true)) {
                    $query->where('status', $status);
                }
            })
            ->when($validated['priority'] ?? null, fn ($query, string $priority) => $query->where('priority', $priority))
            ->when(($validated['is_completed'] ?? null) !== null, fn ($query, bool $completed) => $query->where('is_completed', $completed))
            ->when($validated['due_date_desde'] ?? null, fn ($query, string $desde) => $query->whereDate('due_date', '>=', $desde))
            ->when($validated['due_date_hasta'] ?? null, fn ($query, string $hasta) => $query->whereDate('due_date', '<=', $hasta))
            ->orderByRaw('is_completed ASC, due_date ASC, id DESC')
            ->limit($validated['limit'] ?? 10)
            ->get();

        return Response::json([
            'count' => $tareas->count(),
            'tareas' => $tareas->map(fn (Task $t): array => [
                'id' => $t->id,
                'title' => $t->title,
                'description' => $t->description,
                'status' => $t->status,
                'priority' => $t->priority,
                'due_date' => $t->due_date?->toDateString(),
                'is_completed' => (bool) $t->is_completed,
                'assigned_to_id' => $t->assigned_to,
                'assigned_to' => $t->assignedTo?->name,
                'created_at' => $t->created_at?->toDateTimeString(),
            ])->values(),
        ]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Texto a buscar en el título de la tarea.'),
            'status' => $schema->string()->description('Estado de la tarea según TaskStatus.'),
            'priority' => $schema->string()->description('Prioridad de la tarea.'),
            'is_completed' => $schema->boolean()->description('Filtrar por tareas completadas o pendientes.'),
            'due_date_desde' => $schema->string()->description('Fecha mínima de vencimiento (Y-m-d).'),
            'due_date_hasta' => $schema->string()->description('Fecha máxima de vencimiento (Y-m-d).'),
            'limit' => $schema->integer()->description('Número máximo de tareas (1-100). Por defecto 10.'),
        ];
    }
}
