<?php

namespace App\Actions;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Gasto;
use Illuminate\Support\Carbon;

class CreateGastoAction
{
    public function handle(array $data): Gasto
    {
        $fecha = Carbon::parse($data['fecha'] ?? now()->toDateString());
        $baseImponible = (float) $data['base_imponible'];
        $impuesto = (float) ($data['impuesto'] ?? 0);
        $total = isset($data['total']) ? (float) $data['total'] : round($baseImponible + $impuesto, 2);

        $gasto = new Gasto;
        $gasto->empresa_id = $data['empresa_id'] ?? $this->resolveEmpresaId($data);
        $gasto->cliente_id = $data['cliente_id'] ?? null;
        $gasto->proveedor_id = $data['proveedor_id'] ?? null;
        $gasto->codigo = $data['codigo'] ?? $this->generarCodigo($fecha);
        $gasto->descripcion = $data['descripcion'];
        $gasto->notas = $data['notas'] ?? null;
        $gasto->categoria = $data['categoria'] ?? null;
        $gasto->fecha = $fecha;
        $gasto->base_imponible = $baseImponible;
        $gasto->impuesto = $impuesto;
        $gasto->total = $total;
        $gasto->estado = $data['estado'] ?? 'pendiente';
        $gasto->metodo_pago = $data['metodo_pago'] ?? null;
        $gasto->documento = $data['documento'] ?? null;
        $gasto->deducible = (bool) ($data['deducible'] ?? true);
        $gasto->save();

        return $gasto;
    }

    /**
     * Resolve empresa_id from related records or fallback to the first empresa.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveEmpresaId(array $data): ?int
    {
        $empresaId = null;

        if (! empty($data['proveedor_id'])) {
            $empresaId = Cliente::query()->find($data['proveedor_id'])?->empresa_id;
        }

        if (! $empresaId && ! empty($data['cliente_id'])) {
            $empresaId = Cliente::query()->find($data['cliente_id'])?->empresa_id;
        }

        if (! $empresaId) {
            $empresaId = Empresa::query()->value('id');
        }

        return $empresaId;
    }

    /**
     * Generate a readable expense code.
     */
    private function generarCodigo(Carbon $fecha): string
    {
        $ultimo = Gasto::query()->whereDate('fecha', $fecha->toDateString())->count();

        return 'G-'.$fecha->format('Ymd').'-'.str_pad((string) ($ultimo + 1), 4, '0', STR_PAD_LEFT);
    }
}
