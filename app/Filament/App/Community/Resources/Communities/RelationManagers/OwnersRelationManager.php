<?php

namespace App\Filament\App\Community\Resources\Communities\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OwnersRelationManager extends RelationManager
{
    protected static string $relationship = 'people';

    protected static ?string $title = 'Personas';

    public function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('display_name')->label('Persona'), TextColumn::make('email'), TextColumn::make('pivot.role')->label('Rol')->badge()]);
    }
}
