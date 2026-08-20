<?php

namespace App\Filament\App\NovaHub\Resources\Hoteles\Pages;

use App\Filament\App\NovaHub\Resources\Hoteles\HotelesResource;
use Cheesegrits\FilamentGoogleMaps\Fields\Map;
use Cheesegrits\FilamentGoogleMaps\Helpers\MapsHelper;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Cheesegrits\FilamentGoogleMaps\Concerns\InteractsWithMaps;
use Filament\Schemas\Schema as Form;

class EditHoteles extends EditRecord
{
    use InteractsWithMaps;

    protected static string $resource = HotelesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
