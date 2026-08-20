<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Pagos\Pages;

use App\Filament\App\Resources\Pagos\PagoResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePago extends CreateRecord
{
    protected static string $resource = PagoResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Pago creado correctamente';
    }
}
