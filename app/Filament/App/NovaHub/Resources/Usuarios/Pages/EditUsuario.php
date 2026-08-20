<?php

namespace App\Filament\App\NovaHub\Resources\Usuarios\Pages;

use App\Filament\App\NovaHub\Resources\Usuarios\UsuariosResource;
use App\Models\Taxi\Usuario;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

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
