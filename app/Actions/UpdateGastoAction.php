<?php

namespace App\Actions;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Gasto;
use Illuminate\Support\Carbon;

class UpdateGastoAction
{
    public function handle(int $id, array $data): Gasto
    {
        $gasto = Gasto::query()->findOrFail($id);

        if (isset($data['empresa_id'])) {
            $gasto->empresa_id = $data['empresa_id'];
        } elseif (! $gasto->empresa_id) {
            $gasto->empresa_id = $this->resolveEmpresaId($data, $gasto);
        }

        if (array_key_exists('proveedor_id', $data)) {
            $gasto->proveedor_id = $data['proveedor_id'];
        }

        if (array_key_exists('cliente_id', $data)) {
            $gasto->cliente_id = $data['cliente_id'];
        }

        if (isset($data['fecha'])) {
            $gasto->fecha = Carbon::parse($data['fecha']);
        }

        if (isset($data['descripcion'])) {
            $gasto->descripcion = $data['descripcion'];
        }

        if (isset($data['notas'])) {
            $gasto->notas = $data['notas'];
        }

        if (isset($data['categoria'])) {
            $gasto->categoria = $data['categoria'];
        }

        if (isset($data['estado'])) {
            $gasto->estado = $data['estado'];
        }

        if (isset($data['metodo_pago'])) {
            $gasto->metodo_pago = $data['metodo_pago'];
        }

        if (isset($data['documento'])) {
            $gasto->documento = $data['documento'];
        }

        if (isset($data['deducible'])) {
            $gasto->deducible = (bool) $data['deducible'];
        }

        $baseImponible = array_key_exists('base_imponible', $data)
            ? (float) $data['base_imponible']
            : (float) $gasto->base_imponible;

        $impuesto = array_key_exists('impuesto', $data)
            ? (float) $data['impuesto']
            : (float) $gasto->impuesto;

        $total = isset($data['total'])
            ? (float) $data['total']
            : round($baseImponible + $impuesto, 2);

        $gasto->base_imponible = $baseImponible;
        $gasto->impuesto = $impuesto;
        $gasto->total = $total;
        $gasto->save();

        return $gasto;
    }

    /**
     * Resolve empresa_id from related records or fallback to the first empresa.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveEmpresaId(array $data, Gasto $gasto): ?int
    {
        $empresaId = $data['empresa_id'] ?? null;

        if ($empresaId) {
            return (int) $empresaId;
        }

        $proveedorId = $data['proveedor_id'] ?? $gasto->proveedor_id;
        $clienteId = $data['cliente_id'] ?? $gasto->cliente_id;

        if ($proveedorId) {
            $empresaId = Cliente::query()->find($proveedorId)?->empresa_id;
        }

        if (! $empresaId && $clienteId) {
            $empresaId = Cliente::query()->find($clienteId)?->empresa_id;
        }

        if (! $empresaId) {
            $empresaId = Empresa::query()->value('id');
        }

        return $empresaId;
    }
}
