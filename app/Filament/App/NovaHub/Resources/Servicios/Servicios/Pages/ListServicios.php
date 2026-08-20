<?php

namespace App\Filament\App\NovaHub\Resources\Servicios\Servicios\Pages;

use App\Filament\App\NovaHub\Resources\Servicios\Servicios\ServicioResource;
use Archilex\AdvancedTables\AdvancedTables;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Filters\SelectFilter;

use Illuminate\Database\Eloquent\Model;
use App\Filament\Traits\HasParentResource;
use Archilex\AdvancedTables\Components\PresetView;
use Archilex\AdvancedTables\Filters\TextFilter;
use Archilex\AdvancedTables\Filters\AdvancedFilter;
use Archilex\AdvancedTables\Filters\BooleanFilter;
use Archilex\AdvancedTables\Filters\NumericFilter;
use Archilex\AdvancedTables\Filters\DateFilter;
use Archilex\AdvancedTables\Filters\UserSelectFilter;

class ListServicios extends ListRecords
{


    protected static string $resource = ServicioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data): Model {
                    //$data['taxista_id'] = $this->parent->id;

                    return static::getModel()::create($data);
                })
            ];
    }
}
