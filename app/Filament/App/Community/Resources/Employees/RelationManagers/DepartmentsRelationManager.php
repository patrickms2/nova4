<?php

namespace App\Filament\App\Community\Resources\Employees\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DepartmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'communityDepartments';
    protected static ?string $title = 'Departamentos';

    public function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('name'), TextColumn::make('community.name')->label('Comunidad')])
        ->headerActions([AttachAction::make()->preloadRecordSelect()])
        ->recordActions([DetachAction::make()]);
    }
}
