<?php

namespace App\Mcp\Tools\NovaFactu;

use App\Models\Cliente;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Lista clientes de NovaFact con filtros opcionales por texto (nombre, DNI/CIF o email) y empresa. Devuelve un resumen en JSON de cada cliente, incluyendo su número de conceptos y facturas.')]
class ListClientsTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'search' => 'nullable|string',
            'empresa_id' => 'nullable|integer|exists:empresas,id',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $clientes = Cliente::query()
            ->withCount(['conceptos', 'facturas'])
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('nombretotal', 'like', "%{$search}%")
                        ->orWhere('dni', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($validated['empresa_id'] ?? null, fn ($query, int $empresaId) => $query->where('empresa_id', $empresaId))
            ->orderBy('nombretotal')
            ->limit($validated['limit'] ?? 20)
            ->get();

        return Response::json([
            'count' => $clientes->count(),
            'clientes' => $clientes->map(fn (Cliente $cliente): array => [
                'id' => $cliente->id,
                'nombretotal' => $cliente->nombretotal,
                'dni' => $cliente->dni,
                'email' => $cliente->email,
                'telefono' => $cliente->telefono,
                'empresa_id' => $cliente->empresa_id,
                'conceptos' => $cliente->conceptos_count,
                'facturas' => $cliente->facturas_count,
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
            'search' => $schema->string()->description('Texto a buscar en el nombre, DNI/CIF o email del cliente.'),
            'empresa_id' => $schema->integer()->description('Filtrar por ID de empresa.'),
            'limit' => $schema->integer()->description('Número máximo de clientes a devolver (1-100). Por defecto 20.'),
        ];
    }
}
