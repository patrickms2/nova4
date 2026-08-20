<?php

namespace App\Filament\App\NovaHub\Resources\Usuarios\Pages;

use App\Filament\App\NovaHub\Resources\Usuarios\UsuariosResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Pages\Enums\SubNavigationPosition;

class CreateUsuario extends CreateRecord
{

    protected static string $resource = UsuariosResource::class;
	protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

}
