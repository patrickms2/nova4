<?php

namespace App\Filament\App\Community\Resources\WorkOrders\RelationManagers;

use App\Actions\Community\TransitionWorkOrderTask;
use App\Models\WorkOrderTask;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static ?string $title = 'Tareas';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')->label('Empleado')->relationship('employee', 'name')->searchable()->preload(), 

        TextInput::make('title')->label('Tarea')->required(), Textarea::make('instructions')->label('Instrucciones'), Select::make('priority')->label('Prioridad')->options(['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'])->default('normal'), Select::make('status')->label('Estado')->options(['pending' => 'Pendiente', 'completed' => 'Completada', 'not_done' => 'No realizada', 'cancelled' => 'Cancelada'])->default('pending')]);
    }

    public function table(Table $table): Table
    {
        return $table->recordTitleAttribute('title')->columns([
                        TextColumn::make('employee.name')->label('Empleado')->searchable()->sortable(), 

            TextColumn::make('title')->label('Tarea')->wrap(), 
            TextColumn::make('priority')->label('Prioridad')->badge(), TextColumn::make('status')->label('Estado')->badge(), TextColumn::make('completer.name')->label('Completada por')->placeholder('—'), TextColumn::make('completed_at')->label('Finalizada')->dateTime()->placeholder('—')])
            ->headerActions([
                                AttachAction::make(),
                CreateAction::make()->mutateDataUsing(fn (array $data): array => [...$data, 'source_type' => 'EXTRA', 'created_by' => auth()->id(), 'updated_by' => auth()->id()])])->recordActions([Action::make('complete')->label('Completar')->icon('heroicon-o-check')->color('success')->visible(fn (WorkOrderTask $record): bool => $record->status !== 'completed')->requiresConfirmation()->action(function (WorkOrderTask $record): void {
            app(TransitionWorkOrderTask::class)->handle($record, 'completed', auth()->id());
            Notification::make()->title('Tarea completada')->success()->send();
        }), 
        Action::make('reopen')->label('Reabrir')->visible(fn (WorkOrderTask $record): bool => $record->status === 'completed')->action(fn (WorkOrderTask $record) => app(TransitionWorkOrderTask::class)->handle($record, 'pending', auth()->id()))]);
    }
}
