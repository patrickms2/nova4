<?php

namespace App\Mcp\Tools\NovaFactu;

use App\Models\Concepto;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Lista conceptos de facturación de NovaFact con filtros opcionales por texto, cliente, categoría y recurrencia. Cada concepto incluye precio, descuento, IGIC y retenciones por defecto, usados al generar facturas.')]
class ListConceptsTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'search' => 'nullable|string',
            'cliente_id' => 'nullable|integer|exists:clientes,id',
            'categoria' => 'nullable|string',
            'recurrente' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $conceptos = Concepto::query()
            ->with('cliente:id,nombretotal')
            ->when($validated['search'] ?? null, fn ($query, string $search) => $query->where('concepto', 'like', "%{$search}%"))
            ->when($validated['cliente_id'] ?? null, fn ($query, int $clienteId) => $query->where('cliente_id', $clienteId))
            ->when($validated['categoria'] ?? null, fn ($query, string $categoria) => $query->where('categoria', $categoria))
            ->when(isset($validated['recurrente']), fn ($query) => $query->where('recurrente', (bool) $validated['recurrente']))
            ->orderBy('concepto')
            ->limit($validated['limit'] ?? 20)
            ->get();

        return Response::json([
            'count' => $conceptos->count(),
            'conceptos' => $conceptos->map(fn (Concepto $concepto): array => [
                'id' => $concepto->id,
                'concepto' => $concepto->concepto,
                'cliente_id' => $concepto->cliente_id,
                'cliente' => $concepto->cliente?->nombretotal,
                'unidad' => $concepto->unidad,
                'precio' => (float) $concepto->precio,
                'descuento' => (float) $concepto->descuento,
                'impuesto' => (float) $concepto->impuesto,
                'retenciones' => (float) $concepto->retenciones,
                'categoria' => $concepto->categoria,
                'recurrente' => (bool) $concepto->recurrente,
            ])->values(),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Texto a buscar en el nombre del concepto.'),
            'cliente_id' => $schema->integer()->description('Filtrar por ID de cliente.'),
            'categoria' => $schema->string()->description('Filtrar por categoría (alojamiento, restauracion, transporte, otros).'),
            'recurrente' => $schema->boolean()->description('Filtrar por conceptos recurrentes (true) o no recurrentes (false).'),
            'limit' => $schema->integer()->description('Número máximo de conceptos a devolver (1-100). Por defecto 20.'),
        ];
    }
}
