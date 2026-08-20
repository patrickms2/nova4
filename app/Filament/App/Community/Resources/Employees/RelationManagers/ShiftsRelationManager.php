<?php

namespace App\Filament\App\Community\Resources\Employees\RelationManagers;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
class ShiftsRelationManager extends RelationManager
{
    protected static string $relationship = 'communityShifts';

    protected static ?string $title = 'Turnos';

    public function form(Schema $s): Schema
    {
        return $s->components([Select::make('community_id')->relationship('community', 'name'), Select::make('community_department_id')->relationship('department', 'name'), Select::make('work_order_id')->relationship('workOrder', 'code'), DatePicker::make('shift_date')->required(), TimePicker::make('starts_at')->required(), TimePicker::make('ends_at')->required(), Select::make('status')->options(['planned' => 'Planificado', 'confirmed' => 'Confirmado', 'completed' => 'Realizado', 'cancelled' => 'Cancelado'])->default('planned')]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('shift_date')->date(), TextColumn::make('starts_at'), TextColumn::make('ends_at'), TextColumn::make('department.name')->label('Departamento'), TextColumn::make('workOrder.code')->label('Orden'), TextColumn::make('status')->badge()])->headerActions([CreateAction::make()]);
    }
}
