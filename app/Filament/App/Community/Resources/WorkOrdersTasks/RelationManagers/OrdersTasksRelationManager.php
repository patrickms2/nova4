<?php

namespace App\Filament\App\Community\Resources\WorkOrdersTasks\RelationManagers;

use App\Actions\Community\TransitionWorkOrder;
use App\Actions\Community\TransitionWorkOrderTask;

use App\Models\WorkOrderTask;
use App\Models\WorkOrder;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\App\Community\Resources\WorkOrders\WorkOrderResource;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
class OrdersTasksRelationManager extends RelationManager
{
    protected static string $relationship = 'workOrder';

    protected static ?string $title = 'Orden';

    public function form(Schema $schema): Schema
    {
 return $schema->components([Section::make('Orden')->schema([
            Select::make('community_id')->label('Comunidad')->relationship('community', 'name')->searchable()->preload()->required(), TextInput::make('code')->label('Código')->required()->unique(ignoreRecord: true), DatePicker::make('work_date')->label('Fecha')->required(), Select::make('status')->label('Estado')->options(self::statuses())->default('pending')->required(), TextInput::make('requester_name')->label('Solicitante'), TextInput::make('requester_phone')->label('Teléfono')->tel(), TextInput::make('reference')->label('Referencia'),
        ])->columns(2)]);
     }
    private static function transitionActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
            ViewAction::make(),
            Action::make('start')->label('Iniciar')->icon('heroicon-o-play')->color('warning')->visible(fn (WorkOrder $record): bool => $record->status === 'pending')->requiresConfirmation()->action(fn (WorkOrder $record) => WorkOrderResource::transition($record, 'in_progress')),
            Action::make('finish')->label('Finalizar')->icon('heroicon-o-check-circle')->color('success')->visible(fn (WorkOrder $record): bool => $record->status === 'in_progress')->requiresConfirmation()->action(fn (WorkOrder $record) => WorkOrderResource::transition($record, 'finished')),
            Action::make('reopen')->label('Reabrir')->icon('heroicon-o-arrow-path')->visible(fn (WorkOrder $record): bool => in_array($record->status, ['finished', 'cancelled'], true))->requiresConfirmation()->action(fn (WorkOrder $record) => WorkOrderResource::transition($record, 'pending')),
        ];
    }

    public static function statuses(): array
    {
        return ['pending' => 'Pendiente', 'in_progress' => 'En curso', 'finished' => 'Finalizada', 'cancelled' => 'Cancelada'];
    }
    public function table(Table $table): Table
    {
        return $table->defaultSort('work_date', 'desc')->columns([TextColumn::make('code')->label('Código')->searchable()->sortable(), TextColumn::make('work_date')->label('Fecha')->date()->sortable(), TextColumn::make('community.name')->label('Comunidad')->searchable()->sortable(), TextColumn::make('status')->label('Estado')->badge(), TextColumn::make('starter.name')->label('Empleado')->placeholder('Sin iniciar'), TextColumn::make('progress')->label('Progreso')->state(fn (WorkOrder $record): string => $record->completed_tasks_count.'/'.$record->tasks_count), TextColumn::make('incidents_count')->label('Incidencias')->badge()])->filters([SelectFilter::make('community')->relationship('community', 'name')->searchable()->preload(), SelectFilter::make('status')->options(self::statuses()), Filter::make('today')->label('Hoy')->query(fn (Builder $query): Builder => $query->whereDate('work_date', today())), Filter::make('has_incidents')->label('Con incidencias')->query(fn (Builder $query): Builder => $query->has('incidents'))])->recordActions(self::transitionActions());
    }
}
