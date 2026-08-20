<?php

namespace App\Mcp\Tools\NovaFactu;

use App\Models\Empresa;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Lista empresas emisoras de NovaFact con filtro opcional por texto (nombre o NIF). Incluye el número de clientes y facturas de cada empresa.')]
class ListCompaniesTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'search' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $empresas = Empresa::query()
            ->withCount(['clientes', 'facturas'])
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('empresa', 'like', "%{$search}%")
                        ->orWhere('nombre', 'like', "%{$search}%")
                        ->orWhere('nif', 'like', "%{$search}%");
                });
            })
            ->orderBy('empresa')
            ->limit($validated['limit'] ?? 20)
            ->get();

        return Response::json([
            'count' => $empresas->count(),
            'empresas' => $empresas->map(fn (Empresa $empresa): array => [
                'id' => $empresa->id,
                'empresa' => $empresa->empresa,
                'nombre' => $empresa->nombre,
                'nif' => $empresa->nif,
                'email' => $empresa->email,
                'telefono' => $empresa->telefono,
                'poblacion' => $empresa->poblacion,
                'clientes' => $empresa->clientes_count,
                'facturas' => $empresa->facturas_count,
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
            'search' => $schema->string()->description('Texto a buscar en el nombre o NIF de la empresa.'),
            'limit' => $schema->integer()->description('Número máximo de empresas a devolver (1-100). Por defecto 20.'),
        ];
    }
}
