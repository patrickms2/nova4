<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Hoteles\Pages;

use App\Filament\App\Resources\Hoteles\HotelesResource;
use App\Filament\App\Resources\Hoteles\Widgets\HotelesMapWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHoteles extends ListRecords
{
    protected static string $resource = HotelesResource::class;

    protected static ?string $title = 'Hoteles';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo hotel'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            HotelesMapWidget::class,
        ];
    }
}
