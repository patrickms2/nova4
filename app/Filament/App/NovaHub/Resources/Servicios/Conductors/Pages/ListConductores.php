<?php

namespace App\Filament\App\NovaHub\Resources\Servicios\Conductors\Pages;

use App\Filament\App\NovaHub\Resources\Servicios\Conductors\ConductorResource;
use App\Filament\App\NovaHub\Resources\Servicios\Taxistas\TaxistaResource;
use App\Filament\Traits\HasParentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;
use Archilex\AdvancedTables\Components\PresetView;
use Archilex\AdvancedTables\AdvancedTables;
use Archilex\AdvancedTables\Filters\SelectFilter;
use Archilex\AdvancedTables\Filters\TextFilter;
use Archilex\AdvancedTables\Filters\AdvancedFilter;
use Archilex\AdvancedTables\Filters\BooleanFilter;
use Archilex\AdvancedTables\Filters\NumericFilter;
use Archilex\AdvancedTables\Filters\DateFilter;
use Archilex\AdvancedTables\Filters\UserSelectFilter;

class ListConductores extends ListRecords
{

   //use HasParentResource;
    protected static string $parentResource = TaxistaResource::class;
    protected static string $resource = ConductorResource::class;
    protected static ?string $title = 'Conductores';


}
