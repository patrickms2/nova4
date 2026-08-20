<?php

namespace App\Filament\App\Resources\Usuarios\Pages;

use App\Filament\App\Resources\Usuarios\UsuariosResource;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\CreateRecord;

class CreateUsuario extends CreateRecord
{

    protected static string $resource = UsuariosResource::class;
    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

}
