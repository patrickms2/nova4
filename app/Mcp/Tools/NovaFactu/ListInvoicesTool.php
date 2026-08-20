<?php

namespace App\Mcp\Tools\NovaFactu;

use App\Models\Factura;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Lista facturas de NovaFact con filtros opcionales por texto (número de factura o nombre de cliente), cliente, rango de fechas y estado VeriFactu. Devuelve un resumen en JSON de cada factura.')]
class ListInvoicesTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'search' => 'nullable|string',
            'cliente_id' => 'nullable|integer|exists:clientes,id',
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date',
            'verifactu_status' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $facturas = Factura::query()
            ->with('cliente:id,nombretotal,dni')
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('codfactura', 'like', "%{$search}%")
                        ->orWhereHas('cliente', fn ($query) => $query->where('nombretotal', 'like', "%{$search}%"));
                });
            })
            ->when($validated['cliente_id'] ?? null, fn ($query, int $clienteId) => $query->where('cliente_id', $clienteId))
            ->when($validated['fecha_desde'] ?? null, fn ($query, string $desde) => $query->whereDate('fechaemitido', '>=', $desde))
            ->when($validated['fecha_hasta'] ?? null, fn ($query, string $hasta) => $query->whereDate('fechaemitido', '<=', $hasta))
            ->when($validated['verifactu_status'] ?? null, fn ($query, string $status) => $query->where('verifactu_status', $status))
            ->orderByDesc('fechaemitido')
            ->orderByDesc('id')
            ->limit($validated['limit'] ?? 10)
            ->get();

        return Response::json([
            'count' => $facturas->count(),
            'facturas' => $facturas->map(fn (Factura $factura): array => [
                'id' => $factura->id,
                'codfactura' => $factura->codfactura,
                'fechaemitido' => $factura->fechaemitido?->toDateString(),
                'cliente_id' => $factura->cliente_id,
                'cliente' => $factura->cliente?->nombretotal,
                'baseimponible' => (float) $factura->baseimponible,
                'impuesto' => (float) $factura->impuesto,
                'retenciones' => (float) $factura->retenciones,
                'importe' => (float) $factura->importe,
                'pagada' => (bool) $factura->pagada,
                'rectificativa' => (bool) $factura->rectificativa,
                'verifactu_status' => $factura->verifactu_status,
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
            'search' => $schema->string()->description('Texto a buscar en el número de factura o el nombre del cliente.'),
            'cliente_id' => $schema->integer()->description('Filtrar por ID de cliente.'),
            'fecha_desde' => $schema->string()->description('Fecha mínima de emisión (Y-m-d).'),
            'fecha_hasta' => $schema->string()->description('Fecha máxima de emisión (Y-m-d).'),
            'verifactu_status' => $schema->string()->description('Filtrar por estado VeriFactu (por ejemplo: accepted, sent, rejected).'),
            'limit' => $schema->integer()->description('Número máximo de facturas a devolver (1-100). Por defecto 10.'),
        ];
    }
}
