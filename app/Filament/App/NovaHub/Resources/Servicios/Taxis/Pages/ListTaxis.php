<?php

namespace App\Filament\App\NovaHub\Resources\Servicios\Taxis\Pages;

use App\Filament\App\NovaHub\Resources\Servicios\Taxistas\TaxistaResource;
use App\Filament\App\NovaHub\Resources\Servicios\Taxis\TaxiResource;
use Archilex\AdvancedTables\AdvancedTables;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Traits\HasParentResource;
use Illuminate\Database\Eloquent\Model;
use Filament\Schemas\Components\Tabs\Tab;

class ListTaxis extends ListRecords
{



    //use HasParentResource;
    protected static string $parentResource = TaxistaResource::class;
    protected static string $resource = TaxiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                /*->using(function (array $data): Model {
                $data['usuario_id'] = $this->parent->id;

                return static::getModel()::create($data);
            })*/,
        ];
    }
}
