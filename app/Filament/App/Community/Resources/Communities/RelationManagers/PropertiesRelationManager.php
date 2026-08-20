<?php

namespace App\Filament\App\Community\Resources\Communities\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PropertiesRelationManager extends RelationManager
{
    protected static string $relationship = 'properties';

    protected static ?string $title = 'Propiedades';

    public function form(Schema $s): Schema
    {
        return $s->components([TextInput::make('name')->required(), TextInput::make('unit_reference')->label('Unidad'), TextInput::make('address')->label('Dirección'), TextInput::make('slug')->required(), TextInput::make('timezone')->default('Atlantic/Canary')]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('name')->label('Propiedad'), TextColumn::make('unit_reference')->label('Unidad'), TextColumn::make('address')->label('Dirección'), TextColumn::make('people.display_name')->label('Propietarios')->badge()])
        ->headerActions([CreateAction::make()->mutateDataUsing(fn (array $d): array => [...$d, 'slug' => $d['slug'] ?: Str::slug($d['name']), 'owner_id' => auth()->user()->id, 'is_active' => true])]);
    }
}
