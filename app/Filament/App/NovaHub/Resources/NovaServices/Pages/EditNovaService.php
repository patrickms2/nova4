<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaServices\Pages;

use App\Filament\App\NovaHub\Resources\NovaServices\NovaServiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditNovaService extends EditRecord
{
    protected static string $resource = NovaServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
