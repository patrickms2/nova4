<?php

namespace App\Filament\App\Community\Resources\Employees\Pages;
use App\Filament\App\Community\Resources\Employees\EmployeeResource;
use Filament\Resources\Pages\ManageRelatedRecords;
use Illuminate\Database\Eloquent\Builder;

use App\Filament\App\Community\Resources\WorkOrders\WorkOrderResource;
use App\Actions\Community\TransitionWorkOrder;
use App\Filament\App\Community\Resources\WorkOrders\Pages;
use App\Models\WorkOrder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Actions\Community\TransitionWorkOrderTask;
use App\Models\WorkOrderTask;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;

class ManageEmployeeTasks extends ManageRelatedRecords
{
    protected static string $relationship = 'tasks';

    protected static ?string $title = 'Tareas';
    protected static string $resource = EmployeeResource::class;

    private static function transition2(WorkOrder $record, string $status): void
    {
        app(TransitionWorkOrder::class)->handle($record, $status, auth()->id());
        Notification::make()->title('Orden actualizada')->success()->send();
    }

    private static function transitionActions(): array
    {
        return [
            DetachAction::make(),
            ViewAction::make()->slideOver(),
            EditAction::make()->slideOver(),
            DeleteAction::make(),
            Action::make('start')->label('Iniciar')->icon('heroicon-o-play')->color('warning')->visible(fn (WorkOrder $record): bool => $record->status === 'pending')->requiresConfirmation()->action(fn (WorkOrder $record) => self::transition2($record, 'in_progress')),
            Action::make('finish')->label('Finalizar')->icon('heroicon-o-check-circle')->color('success')->visible(fn (WorkOrder $record): bool => $record->status === 'in_progress')->requiresConfirmation()->action(fn (WorkOrder $record) => self::transition2($record, 'finished')),
            Action::make('reopen')->label('Reabrir')->icon('heroicon-o-arrow-path')->visible(fn (WorkOrder $record): bool => in_array($record->status, ['finished', 'cancelled'], true))->requiresConfirmation()->action(fn (WorkOrder $record) => self::transition2($record, 'pending')),
        ];
    }

    public static function statuses(): array
    {
        return ['pending' => 'Pendiente', 'in_progress' => 'En curso', 'finished' => 'Finalizada', 'cancelled' => 'Cancelada'];
    }

    public function form(Schema $schema): Schema
    {
 return $schema->components([           
             Select::make('work_order_id')->label('Orden de trabajo')->relationship('workOrder', 'code')->searchable()->preload(), 
TextInput::make('title')->label('Tarea')->required(), 
Textarea::make('instructions')->label('Instrucciones'), 
Select::make('priority')->label('Prioridad')->options(['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'])->default('normal'), Select::make('status')->label('Estado')->options(['pending' => 'Pendiente', 'completed' => 'Completada', 'not_done' => 'No realizada', 'cancelled' => 'Cancelada'])->default('pending')]);

    }

    public function table(Table $table): Table
    {
          return $table->recordTitleAttribute('title')->columns([
                            TextColumn::make('workOrder.reference')->label('Orden')->placeholder('—'), 

                TextColumn::make('title')->label('Tarea')->wrap(), 
                TextColumn::make('priority')->label('Prioridad')->badge(), 
                TextColumn::make('status')->label('Estado')->badge(), 
                TextColumn::make('completer.name')->label('Completada por')->placeholder('—'), 
                TextColumn::make('completed_at')->label('Finalizada')->dateTime()->placeholder('—')])
                ->headerActions([CreateAction::make()->mutateDataUsing(fn (array $data): array => [...$data, 'source_type' => 'EXTRA', 'created_by' => auth()->id(), 'updated_by' => auth()->id()])])
                ->recordActions([Action::make('complete')->label('Completar')->icon('heroicon-o-check')->color('success')->visible(fn (WorkOrderTask $record): bool => $record->status !== 'completed')->requiresConfirmation()->action(function (WorkOrderTask $record): void {
            app(TransitionWorkOrderTask::class)->handle($record, 'completed', auth()->id());
            Notification::make()->title('Tarea completada')->success()->send();
        }), Action::make('reopen')->label('Reabrir')->visible(fn (WorkOrderTask $record): bool => $record->status === 'completed')->action(fn (WorkOrderTask $record) => app(TransitionWorkOrderTask::class)->handle($record, 'pending', auth()->id()))]);
  
    }
}
