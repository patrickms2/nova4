<?php

namespace App\Mcp\Tools\NovaFactu;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * MCP Tool: List clients with numbered selection
 *
 * Returns a numbered list of clients for easy selection in WhatsApp.
 * Each client includes their ID, name, and company information.
 */
class ListNumberedClientsTool
{
    /**
     * Handle the request to list numbered clients.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'search' => 'nullable|string',
            'empresa_id' => 'nullable|integer|exists:empresas,id',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $limit = $validated['limit'] ?? 20;
        $search = $validated['search'] ?? null;
        $empresaId = $validated['empresa_id'] ?? null;

        $query = Cliente::query()
            ->with('empresa:id,nombre,email')
            ->orderBy('nombretotal');

        if ($search) {
            $query->where('nombretotal', 'like', "%{$search}%");
        }

        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        $clientes = $query->limit($limit)->get();

        $numberedClients = $clientes->map(function (Cliente $cliente, int $index) {
            return [
                'number' => $index + 1,
                'id' => $cliente->id,
                'nombre' => $cliente->nombretotal,
                'empresa' => $cliente->empresa ? [
                    'id' => $cliente->empresa->id,
                    'nombre' => $cliente->empresa->nombre,
                    'email' => $cliente->empresa->email,
                ] : null,
                'conceptos_count' => $cliente->conceptos()->count(),
            ];
        });

        return Response::json([
            'count' => $clientes->count(),
            'clientes' => $numberedClients->values(),
        ]);
    }
}
