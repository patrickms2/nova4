<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ServiciosDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-signal';

    protected static ?string $navigationLabel = 'Solicitudes Taxi';

    protected static ?string $title = 'Solicitudes de Taxi - Hoteles';

    protected static string|null|\UnitEnum $navigationGroup = 'Departamentos';

    protected static ?int $navigationSort = 12;
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.app.pages.servicios-dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            //\App\Filament\App\Widgets\SolicitudesBubbleMapChart::class,
        ];
    }
}
