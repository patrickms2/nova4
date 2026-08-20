<?php

namespace App\Filament\App\Community\Resources\Owners\RelationManagers;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;

class AppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'communityAppointments';

    protected static ?string $title = 'Cita previa';

    public function form(Schema $s): Schema
    {
        return $s->components([Select::make('community_id')->relationship('community', 'name')->required(), Select::make('property_id')->relationship('property', 'name'), Select::make('community_department_id')->relationship('department', 'name'), TextInput::make('title')->required(), DateTimePicker::make('starts_at')->label('Inicio')->required(), DateTimePicker::make('ends_at')->label('Fin'), Select::make('status')->options(['scheduled' => 'Programada', 'confirmed' => 'Confirmada', 'completed' => 'Realizada', 'cancelled' => 'Cancelada'])->default('scheduled'), TextInput::make('location')->label('Lugar')]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('starts_at')->label('Fecha')->dateTime(), TextColumn::make('title')->label('Cita'), TextColumn::make('department.name')->label('Departamento'), TextColumn::make('status')->label('Estado')->badge()])->headerActions([CreateAction::make()->mutateDataUsing(fn (array $d): array => [...$d, 'created_by' => auth()->user->id])]);
    }
}
