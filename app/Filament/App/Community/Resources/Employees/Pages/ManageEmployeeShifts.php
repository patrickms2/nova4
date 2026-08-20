<?php

namespace App\Filament\App\Community\Resources\Employees\Pages;

use App\Filament\App\Community\Resources\Employees\EmployeeResource;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\Pages\ManageRelatedRecords;
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
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
class ManageEmployeeShifts extends ManageRelatedRecords
{
    protected static string $resource = EmployeeResource::class;

    protected static string $relationship = 'communityShifts';

    protected static ?string $navigationLabel = 'Turnos y cuadrante';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([Select::make('community_id')->relationship('community', 'name'), Select::make('community_department_id')->relationship('department', 'name'), Select::make('work_order_id')->relationship('workOrder', 'code'), DatePicker::make('shift_date')->label('Fecha')->required(), TimePicker::make('starts_at')->label('Inicio')->required(), TimePicker::make('ends_at')->label('Fin')->required(), Select::make('status')->options(['planned' => 'Planificado', 'confirmed' => 'Confirmado', 'completed' => 'Completado', 'cancelled' => 'Cancelado'])->default('planned')]);
    }

    public function table(Table $table): Table
    {
        return $table->defaultSort('shift_date')->columns([TextColumn::make('shift_date')->label('Fecha')->date(), TextColumn::make('starts_at')->label('Inicio')->time('H:i'), TextColumn::make('ends_at')->label('Fin')->time('H:i'), TextColumn::make('department.name')->label('Departamento'), TextColumn::make('workOrder.code')->label('Orden'), TextColumn::make('status')->label('Estado')->badge()])
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
