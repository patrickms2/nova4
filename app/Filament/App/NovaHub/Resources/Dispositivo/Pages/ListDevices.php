<?php

namespace App\Filament\App\NovaHub\Resources\Dispositivo\Pages;

use App\Filament\App\NovaHub\Resources\Dispositivo\DispositivoResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Widgets\TrackarStatsWidget;

class ListDevices extends ListRecords
{
    protected static string $resource = DispositivoResource::class;
    public $gpsLogs;

    public  function getHeaderWidgets(): array
    {
        return [
            TrackarStatsWidget::class,
        ];
    }
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
