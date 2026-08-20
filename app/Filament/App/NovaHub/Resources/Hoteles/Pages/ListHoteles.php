<?php

namespace App\Filament\App\NovaHub\Resources\Hoteles\Pages;

use App\Filament\App\NovaHub\Resources\Hoteles\HotelesResource;
use App\Models\Taxi\EstadosUsuario;
use App\Models\Taxi\Hotel;
use App\Models\Taxi\Taxista;
use Archilex\AdvancedTables\AdvancedTables;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListHoteles extends ListRecords
{

    protected static string $resource = HotelesResource::class;
    protected static ?string $title = 'Hoteles';


    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth(Width::ScreenTwoExtraLarge),

        ];
    }

}
