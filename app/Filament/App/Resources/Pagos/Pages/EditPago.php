<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Pagos\Pages;

use App\Filament\App\Resources\Pagos\PagoResource;
use Filament\Resources\Pages\EditRecord;

class EditPago extends EditRecord
{
    protected static string $resource = PagoResource::class;

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Pago actualizado correctamente';
    }

    protected function getDeletedNotificationTitle(): ?string
    {
        return 'Pago eliminado correctamente';
    }
}
