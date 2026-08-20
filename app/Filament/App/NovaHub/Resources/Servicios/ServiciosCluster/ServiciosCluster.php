<?php

namespace App\Filament\App\NovaHub\Resources\Servicios\ServiciosCluster;

use App\Models\Taxi\Servicio;
use BackedEnum;
use Filament\Clusters\Cluster;
use UnitEnum;
use Filament\Tables\Filters\Filter;
use Archilex\AdvancedTables\Filters\SelectFilter;
use Archilex\AdvancedTables\Filters\TextFilter;
use Archilex\AdvancedTables\Filters\AdvancedFilter;
use Archilex\AdvancedTables\Filters\BooleanFilter;
use Archilex\AdvancedTables\Filters\NumericFilter;
class ServiciosCluster extends Cluster
{

    protected static string | UnitEnum | null $navigationGroup = 'Taxis';

    protected static ?int $navigationSort = -4;

    protected static ?string $slug = 'servicios';
        protected static ?string $model = Servicio::class;


}
