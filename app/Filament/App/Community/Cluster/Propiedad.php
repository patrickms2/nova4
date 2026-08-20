<?php

namespace App\Filament\App\Community\Cluster;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Propiedad extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static string|UnitEnum|null $navigationGroup = 'Propiedad';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    protected static ?int $navigationSort = 10;
}
