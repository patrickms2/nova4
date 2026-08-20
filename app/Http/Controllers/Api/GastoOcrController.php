<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateGastoFromReceiptAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GastoOcrController extends Controller
{
    /**
     * Create an expense from a receipt photo using OCR.
     */
    public function __invoke(Request $request, CreateGastoFromReceiptAction $action): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:10240'],
        ]);

        $file = $request->file('image');
        $storedPath = $file->store('gastos/tickets', 'public');

        try {
            $result = $action->handle(
                imagePath: $file->getRealPath(),
                mimeType: $file->getMimeType(),
                storedPath: $storedPath,
                userId: $request->user()?->id,
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'error' => 'No se pudieron extraer los datos de la imagen.',
            ], 422);
        }

        $gasto = $result['gasto'];

        return response()->json([
            'success' => true,
            'message' => sprintf(
                'Gasto creado: %s — %s €',
                $gasto->descripcion,
                number_format((float) $gasto->total, 2, ',', '.'),
            ),
            'data' => [
                'id' => $gasto->id,
                'codigo' => $gasto->codigo,
                'descripcion' => $gasto->descripcion,
                'fecha' => $gasto->fecha?->toDateString(),
                'base_imponible' => (float) $gasto->base_imponible,
                'impuesto' => (float) $gasto->impuesto,
                'total' => (float) $gasto->total,
                'proveedor_id' => $gasto->proveedor_id,
                'proveedor' => $gasto->proveedor?->nombretotal,
                'documento' => $gasto->documento,
                'ocr' => $result['ocr'],
            ],
        ], 201);
    }
}
