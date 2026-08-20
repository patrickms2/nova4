<?php

namespace App\Filament\App\Community\Resources\Communities\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class DepartmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'departments';

    protected static ?string $title = 'Departamentos';

    public function form(Schema $s): Schema
    {
        return $s->components([TextInput::make('name')->required(), TextInput::make('slug')->required(), TextInput::make('description'), Toggle::make('is_active')->default(true)]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('name'), TextColumn::make('employees_count')->counts('employees')->label('Empleados'), TextColumn::make('shifts_count')->counts('shifts')->label('Turnos')])->headerActions([CreateAction::make()->mutateDataUsing(fn (array $d): array => [...$d, 'slug' => $d['slug'] ?: Str::slug($d['name'])])]);
    }
}
