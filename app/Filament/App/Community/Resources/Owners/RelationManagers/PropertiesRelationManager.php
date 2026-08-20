<?php

namespace App\Filament\App\Community\Resources\Owners\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
class PropertiesRelationManager extends RelationManager
{
    protected static string $relationship = 'properties';

    protected static ?string $title = 'Propiedades';
    public function form(Schema $s): Schema
    {
        return $s->components([TextInput::make('name')->required(), TextInput::make('unit_reference')->label('Unidad'), TextInput::make('address')->label('Dirección'), TextInput::make('slug')->required(), TextInput::make('timezone')->default('Atlantic/Canary')]);
    }
    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->label('Propiedad'), TextColumn::make('unit_reference')->label('Unidad'), TextColumn::make('community.name')->label('Comunidad'), TextColumn::make('address')->label('Dirección')])
         ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
