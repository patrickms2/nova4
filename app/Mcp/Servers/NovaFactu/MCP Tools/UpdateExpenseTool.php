<?php

namespace App\Mcp\Tools\NovaFactu;

use App\Actions\UpdateGastoAction;
use App\Models\Cliente;
use App\Models\Gasto;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Actualiza un gasto existente en NovaFact. Se puede indicar el proveedor por ID o por nombre. Si no se indica empresa_id, se intenta heredar del proveedor o del cliente. Recalcula el total como base_imponible + impuesto si no se indica. Devuelve el gasto actualizado en JSON.')]
class UpdateExpenseTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:gastos,id',
            'descripcion' => 'nullable|string|max:255',
            'fecha' => 'nullable|date',
            'base_imponible' => 'nullable|numeric|min:0',
            'impuesto' => 'nullable|numeric|min:0',
            'total' => 'nullable|numeric|min:0',
            'categoria' => 'nullable|string|in:suministros,alquiler,nomina,seguros,servicios,marketing,impuestos,mantenimiento,otros',
            'estado' => 'nullable|string|in:pendiente,pagado,cancelado',
            'proveedor_id' => 'nullable|integer|exists:clientes,id',
            'proveedor' => 'nullable|string',
            'cliente_id' => 'nullable|integer|exists:clientes,id',
            'cliente' => 'nullable|string',
            'empresa_id' => 'nullable|integer|exists:empresas,id',
            'metodo_pago' => 'nullable|string|max:80',
            'notas' => 'nullable|string',
            'documento' => 'nullable|string|max:255',
            'deducible' => 'nullable|boolean',
        ]);

        $gasto = Gasto::query()->findOrFail((int) $validated['id']);

        $proveedor = $this->resolvePersona($validated, 'proveedor_id', 'proveedor', $gasto->proveedor_id);
        if ($proveedor instanceof Response) {
            return $proveedor;
        }

        $cliente = $this->resolvePersona($validated, 'cliente_id', 'cliente', $gasto->cliente_id);
        if ($cliente instanceof Response) {
            return $cliente;
        }

        $updateData = [
            'proveedor_id' => $proveedor?->id,
            'cliente_id' => $cliente?->id,
            'empresa_id' => $validated['empresa_id'] ?? null,
        ];

        foreach (['descripcion', 'fecha', 'categoria', 'estado', 'metodo_pago', 'notas', 'documento', 'base_imponible', 'impuesto', 'total', 'deducible'] as $field) {
            if (array_key_exists($field, $validated)) {
                $updateData[$field] = $validated[$field];
            }
        }

        $gasto = app(UpdateGastoAction::class)->handle($gasto->id, $updateData);

        return Response::json([
            'id' => $gasto->id,
            'codigo' => $gasto->codigo,
            'descripcion' => $gasto->descripcion,
            'categoria' => $gasto->categoria,
            'fecha' => $gasto->fecha->toDateString(),
            'proveedor_id' => $gasto->proveedor_id,
            'proveedor' => $gasto->proveedor?->nombretotal,
            'cliente_id' => $gasto->cliente_id,
            'cliente' => $gasto->cliente?->nombretotal,
            'empresa_id' => $gasto->empresa_id,
            'base_imponible' => (float) $gasto->base_imponible,
            'impuesto' => (float) $gasto->impuesto,
            'total' => (float) $gasto->total,
            'estado' => $gasto->estado,
            'metodo_pago' => $gasto->metodo_pago,
            'deducible' => (bool) $gasto->deducible,
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
            'id' => $schema->integer()->description('ID del gasto a actualizar (obligatorio).'),
            'descripcion' => $schema->string()->description('Nueva descripción del gasto.'),
            'fecha' => $schema->string()->description('Nueva fecha del gasto en formato Y-m-d.'),
            'base_imponible' => $schema->number()->description('Nueva base imponible.'),
            'impuesto' => $schema->number()->description('Nuevo importe del impuesto.'),
            'total' => $schema->number()->description('Nuevo total. Si no se indica, se calcula como base_imponible + impuesto.'),
            'categoria' => $schema->string()->description('Nueva categoría: suministros, alquiler, nomina, seguros, servicios, marketing, impuestos, mantenimiento, otros.'),
            'estado' => $schema->string()->description('Nuevo estado: pendiente, pagado o cancelado.'),
            'proveedor_id' => $schema->integer()->description('ID del proveedor. Alternativa a "proveedor".'),
            'proveedor' => $schema->string()->description('Nombre (o parte) del proveedor. Alternativa a "proveedor_id".'),
            'cliente_id' => $schema->integer()->description('ID del cliente asociado. Alternativa a "cliente".'),
            'cliente' => $schema->string()->description('Nombre (o parte) del cliente asociado. Alternativa a "cliente_id".'),
            'empresa_id' => $schema->integer()->description('ID de la empresa emisora. Si no se indica, se hereda del proveedor o cliente.'),
            'metodo_pago' => $schema->string()->description('Método de pago.'),
            'notas' => $schema->string()->description('Notas internas.'),
            'documento' => $schema->string()->description('Número de documento o factura del proveedor.'),
            'deducible' => $schema->boolean()->description('Indica si el gasto es deducible.'),
        ];
    }

    /**
     * Resolve a person (cliente/proveedor) by ID or name, falling back to current value.
     *
     * @param  array<string, mixed>  $validated
     */
    private function resolvePersona(array $validated, string $idKey, string $nameKey, ?int $currentId): Cliente|Response|null
    {
        $id = $validated[$idKey] ?? null;
        $nombre = trim((string) ($validated[$nameKey] ?? ''));

        if ($id === null && $nombre === '' && $currentId) {
            return Cliente::query()->find($currentId) ?? null;
        }

        if ($id) {
            return Cliente::query()->find($id) ?? Response::error("No se encontró el cliente con ID {$id}.");
        }

        if ($nombre === '') {
            return null;
        }

        $clientes = Cliente::query()
            ->where('nombretotal', 'like', "%{$nombre}%")
            ->limit(5)
            ->get();

        if ($clientes->isEmpty()) {
            return Response::error("No se encontró ningún cliente/proveedor que coincida con \"{$nombre}\".");
        }

        if ($clientes->count() > 1) {
            $opciones = $clientes->map(fn (Cliente $c): string => "{$c->id}: {$c->nombretotal}")->implode('; ');

            return Response::error("Hay varios resultados para \"{$nombre}\". Usa {$idKey}: {$opciones}");
        }

        return $clientes->first();
    }

}
