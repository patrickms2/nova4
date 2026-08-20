<?php

namespace App\Mcp\Tools\CasaElPatio;

use App\Models\RentalIncident;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Lista incidencias de Casa El Patio con filtros por propiedad, estado, prioridad, fechas y búsqueda por título. Devuelve un resumen en JSON.')]
class ListIncidentsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'search' => 'nullable|string',
            'rental_property_id' => 'nullable|integer|exists:rental_properties,id',
            'status' => 'nullable|string',
            'priority' => 'nullable|string',
            'created_at_desde' => 'nullable|date',
            'created_at_hasta' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $incidencias = RentalIncident::query()
            ->with(['rentalProperty:id,name'])
            ->when($validated['search'] ?? null, fn ($query, string $search) => $query->where('title', 'like', "%{$search}%"))
            ->when($validated['rental_property_id'] ?? null, fn ($query, int $id) => $query->where('rental_property_id', $id))
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['priority'] ?? null, fn ($query, string $priority) => $query->where('priority', $priority))
            ->when($validated['created_at_desde'] ?? null, fn ($query, string $desde) => $query->whereDate('created_at', '>=', $desde))
            ->when($validated['created_at_hasta'] ?? null, fn ($query, string $hasta) => $query->whereDate('created_at', '<=', $hasta))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($validated['limit'] ?? 10)
            ->get();

        return Response::json([
            'count' => $incidencias->count(),
            'incidencias' => $incidencias->map(fn (RentalIncident $i): array => [
                'id' => $i->id,
                'property_id' => $i->rental_property_id,
                'property' => $i->rentalProperty?->name,
                'title' => $i->title,
                'description' => $i->description,
                'status' => $i->status,
                'priority' => $i->priority,
                'created_at' => $i->created_at?->toDateTimeString(),
                'updated_at' => $i->updated_at?->toDateTimeString(),
            ])->values(),
        ]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Texto a buscar en el título de la incidencia.'),
            'rental_property_id' => $schema->integer()->description('Filtrar por ID de propiedad.'),
            'status' => $schema->string()->description('Estado de la incidencia.'),
            'priority' => $schema->string()->description('Prioridad de la incidencia.'),
            'created_at_desde' => $schema->string()->description('Fecha mínima de creación (Y-m-d).'),
            'created_at_hasta' => $schema->string()->description('Fecha máxima de creación (Y-m-d).'),
            'limit' => $schema->integer()->description('Número máximo de incidencias (1-100). Por defecto 10.'),
        ];
    }
}
