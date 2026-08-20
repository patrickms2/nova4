<?php

namespace App\Actions;

use App\Models\Cliente;
use App\Models\Gasto;

class CreateGastoFromReceiptAction
{
    /**
     * Run OCR on a receipt image and create the Gasto with its proveedor.
     *
     * @return array{gasto: Gasto, ocr: array<string, mixed>}
     */
    public function handle(string $imagePath, ?string $mimeType, ?string $storedPath = null, ?int $userId = null): array
    {
        $response = app(ExtractReceiptData::class)->handle($imagePath, $mimeType);

        $empresa = $response['empresa'] ?? null;
        $concepto = $response['concepto'] ?? null;

        $proveedor = $empresa ? $this->resolveProveedor($empresa) : null;

        $gasto = new Gasto([
            'descripcion' => trim(implode(' - ', array_filter([$empresa, $concepto]))) ?: 'Gasto escaneado',
            'fecha' => $response['fecha'] ?? now()->toDateString(),
            'base_imponible' => $response['base_imponible'] ?? 0,
            'impuesto' => $response['impuesto'] ?? 0,
            'total' => $response['total'] ?? 0,
            'notas' => 'ocr',
            'type' => 'expense',
            'proveedor_id' => $proveedor?->id,
        ]);
        $gasto->empresa_id = $proveedor?->empresa_id;
        $gasto->user_id = $userId;
        $gasto->documento = $storedPath;
        $gasto->save();

        ds($gasto->toArray());

        return [
            'gasto' => $gasto,

        ];
    }

    /**
     * Find or create the proveedor (Cliente) by name.
     */
    public function resolveProveedor(string $nombre): Cliente
    {
        $proveedor = Cliente::query()
            ->whereRaw('LOWER(nombretotal) = ?', [mb_strtolower($nombre)])
            ->first();

        if (! $proveedor) {
            $proveedor = Cliente::query()
                ->where('nombretotal', 'like', '%'.$nombre.'%')
                ->first();
        }

        if (! $proveedor) {
            $proveedor = Cliente::create([
                'codcliente' => 'PROV-'.((int) Cliente::query()->max('id') + 1),
                'nombretotal' => $nombre,
                'fechaalta' => now()->toDateString(),
                'observaciones' => 'Creado automáticamente por OCR de gastos',
            ]);
        }

        return $proveedor;
    }
}
