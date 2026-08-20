<?php

namespace App\Mcp\Tools\NovaFactu;

use App\Models\Gasto;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Lista gastos de NovaFact con filtros opcionales por texto, categoría, estado (pendiente, pagado, cancelado), proveedor y rango de fechas. Devuelve un resumen en JSON de cada gasto con base imponible, impuesto y total.')]
class ListExpensesTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'search' => 'nullable|string',
            'categoria' => 'nullable|string',
            'estado' => 'nullable|string|in:pendiente,pagado,cancelado',
            'proveedor_id' => 'nullable|integer|exists:clientes,id',
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $gastos = Gasto::query()
            ->with('proveedor:id,nombretotal')
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('descripcion', 'like', "%{$search}%")
                        ->orWhere('codigo', 'like', "%{$search}%");
                });
            })
            ->when($validated['categoria'] ?? null, fn ($query, string $categoria) => $query->where('categoria', $categoria))
            ->when($validated['estado'] ?? null, fn ($query, string $estado) => $query->where('estado', $estado))
            ->when($validated['proveedor_id'] ?? null, fn ($query, int $proveedorId) => $query->where('proveedor_id', $proveedorId))
            ->when($validated['fecha_desde'] ?? null, fn ($query, string $desde) => $query->whereDate('fecha', '>=', $desde))
            ->when($validated['fecha_hasta'] ?? null, fn ($query, string $hasta) => $query->whereDate('fecha', '<=', $hasta))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit($validated['limit'] ?? 10)
            ->get();

        return Response::json([
            'count' => $gastos->count(),
            'gastos' => $gastos->map(fn (Gasto $gasto): array => [
                'id' => $gasto->id,
                'codigo' => $gasto->codigo,
                'descripcion' => $gasto->descripcion,
                'categoria' => $gasto->categoria,
                'fecha' => $gasto->fecha?->toDateString(),
                'proveedor_id' => $gasto->proveedor_id,
                'proveedor' => $gasto->proveedor?->nombretotal,
                'base_imponible' => (float) $gasto->base_imponible,
                'impuesto' => (float) $gasto->impuesto,
                'total' => (float) $gasto->total,
                'estado' => $gasto->estado,
                'metodo_pago' => $gasto->metodo_pago,
                'deducible' => (bool) $gasto->deducible,
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
            'search' => $schema->string()->description('Texto a buscar en la descripción o el código del gasto.'),
            'categoria' => $schema->string()->description('Filtrar por categoría (suministros, alquiler, nomina, seguros, servicios, marketing, impuestos, mantenimiento, otros).'),
            'estado' => $schema->string()->description('Filtrar por estado: pendiente, pagado o cancelado.'),
            'proveedor_id' => $schema->integer()->description('Filtrar por ID de proveedor.'),
            'fecha_desde' => $schema->string()->description('Fecha mínima del gasto (Y-m-d).'),
            'fecha_hasta' => $schema->string()->description('Fecha máxima del gasto (Y-m-d).'),
            'limit' => $schema->integer()->description('Número máximo de gastos a devolver (1-100). Por defecto 10.'),
        ];
    }
}
