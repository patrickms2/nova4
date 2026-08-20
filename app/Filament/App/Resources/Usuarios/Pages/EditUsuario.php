<?php

namespace App\Filament\App\Resources\Usuarios\Pages;

use App\Filament\App\Resources\Usuarios\UsuariosResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Tables\Actions\ViewAction;

class EditUsuario extends EditRecord
{

    protected static string $resource = UsuariosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
