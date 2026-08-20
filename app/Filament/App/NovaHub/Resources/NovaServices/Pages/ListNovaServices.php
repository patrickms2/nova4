<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaServices\Pages;

use App\Filament\App\NovaHub\Resources\NovaServices\NovaServiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListNovaServices extends ListRecords
{
    protected static string $resource = NovaServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nueva categoría'),
        ];
    }
}
