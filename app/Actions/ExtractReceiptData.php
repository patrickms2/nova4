<?php

namespace App\Actions;

use App\Ai\Agents\GastoOcrAgent;
use Laravel\Ai\Files\Image;

class ExtractReceiptData
{
    /**
     * @return array{empresa: ?string, fecha: ?string, base_imponible: float|int|null, impuesto: float|int|null, total: float|int|null, concepto: ?string}
     */
    public function handle(string $imagePath, ?string $mimeType): array
    {
        return (new GastoOcrAgent)->prompt(
            'Extrae los datos del gasto de la imagen adjunta.',
            attachments: [Image::fromPath($imagePath, $mimeType)],
        )->toArray();
    }
}
