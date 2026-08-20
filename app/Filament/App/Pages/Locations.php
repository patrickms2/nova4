<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Filament\Pages\CarTracking;
use BackedEnum;
use UnitEnum;

class Locations extends CarTracking
{
    protected string $view = 'filament.app.pages.locations';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-map';

    protected static string|UnitEnum|null $navigationGroup = 'Servicios de Taxista';

    protected static ?string $navigationLabel = 'GPS Mapa2';

    protected static ?string $title = 'GPS Mapa2';

    protected static ?int $navigationSort = 2;

    protected static bool $shouldRegisterNavigation = false;
}
