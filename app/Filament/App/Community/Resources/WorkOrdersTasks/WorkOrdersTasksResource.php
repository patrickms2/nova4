<?php

namespace App\Filament\App\Community\Resources\WorkOrdersTasks;

use App\Actions\Community\TransitionWorkOrderTask;
use App\Models\WorkOrderTask;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use Filament\Forms\Components\Textarea;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;

class WorkOrdersTasksResource extends Resource
{

    protected static ?string $model = WorkOrderTask::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Tareas';

    protected static ?string $modelLabel = 'Tarea';

    protected static ?string $pluralModelLabel = 'Tareas';
    protected static string|\UnitEnum|null $navigationGroup = 'Mantenimiento';
    protected static ?string $navigationParentGroup = 'Órdenes';
    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'Mantenimiento';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([Section::make('Orden')->schema([
            TextInput::make('title')->label('Tarea')->required(), 
            Textarea::make('instructions')->label('Instrucciones'), 
            Select::make('priority')->label('Prioridad')->options(['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'])->default('normal'), 
            Select::make('status')->label('Estado')->options(['pending' => 'Pendiente', 'resolved' => 'Resuelta', 'in_progress' => 'En progreso', 'cancelled' => 'Cancelada'])->default('pending'),
            Select::make('community_id')->label('Comunidad')->relationship('workOrder.community', 'name')->searchable()->preload()->required(), 
            Select::make('work_order_id')->label('Orden')->relationship('workOrder', 'reference')->searchable()->preload()->required(), 
          
        ])->columns(2)]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Orden')->schema([TextEntry::make('code')->label('Código'), TextEntry::make('workOrder.community.name')->label('Comunidad'), TextEntry::make('work_date')->label('Fecha')->date(), TextEntry::make('status')->label('Estado')->badge()])->columns(2),
            Section::make('Resumen')->schema([TextEntry::make('tasks_count')->label('Tareas'), TextEntry::make('completed_tasks_count')->label('Completadas'), TextEntry::make('incidents_count')->label('Incidencias'), TextEntry::make('comments_count')->label('Comentarios'), TextEntry::make('photos_count')->label('Fotos')])->columns(5),
        ]);
    }

    public static function table(Table $table): Table
    {
 return $table->recordTitleAttribute('title')->columns([
        TextColumn::make('workOrder.reference')->label('Orden')->wrap(), 
        TextColumn::make('workOrder.work_date')->label('Fecha')->wrap(), 

        TextColumn::make('community.name')->label('Comunidad')->wrap(), 
                        TextColumn::make('employee.name')->label('Empleado')->searchable()->sortable(), 

    TextColumn::make('title')->label('Tarea')->wrap(), 
    TextColumn::make('priority')->label('Prioridad')->badge(), 
    TextColumn::make('status')->label('Estado')->badge()->color(function (string $state): string { 
        if($state == 'resolved') return 'success';
        elseif($state == 'in_progress') return 'info';
        elseif($state == 'pending') return 'warning';
        else return 'gray';
    }),
    TextColumn::make('completer.name')->label('Completada por')->placeholder('—'), TextColumn::make('completed_at')->label('Finalizada')->dateTime()->placeholder('—')])
        ->headerActions([
            CreateAction::make()->mutateDataUsing(fn (array $data): array => [...$data, 'source_type' => 'EXTRA', 'created_by' => auth()->id(), 'updated_by' => auth()->id()])
        ])
        ->recordActions([
                        EditAction::make(),
            DeleteAction::make(),
            ViewAction::make(),
        Action::make('complete')->label('Completar')->icon('heroicon-o-check')->color('success')->visible(fn (WorkOrderTask $record): bool => $record->status !== 'resolved')->requiresConfirmation()->action(function (WorkOrderTask $record): void {
                    app(TransitionWorkOrderTask::class)->handle($record, 'resolved', auth()->id());
                    Notification::make()->title('Tarea resuelta')->success()->send(); }), 
            Action::make('reopen')->label('Reabrir')->visible(fn (WorkOrderTask $record): bool => $record->status === 'resolved')->action(fn (WorkOrderTask $record) => app(TransitionWorkOrderTask::class)->handle($record, 'pending', auth()->id()))
        ]);
      }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['community','employee','workOrder'])->withCount(['workOrder','incidents', 'incidents as completed_incidents_count' => fn (Builder $query) => $query->where('status', 'completed'),  'comments', 'photos']);
    }

    public static function getRelations(): array
    {
        return [RelationManagers\OrdersTasksRelationManager::class, RelationManagers\IncidentsTasksRelationManager::class, RelationManagers\CommentsTasksRelationManager::class, RelationManagers\PhotosTasksRelationManager::class];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListWorkOrders::route('/'), 'create' => Pages\CreateWorkOrder::route('/create'), 'view' => Pages\ViewWorkOrder::route('/{record}'), 'edit' => Pages\EditWorkOrder::route('/{record}/edit')];
    }

    public static function statuses(): array
    {
        return ['pending' => 'Pendiente', 'in_progress' => 'En curso', 'resolved' => 'Resuelta', 'cancelled' => 'Cancelada'];
    }
    public static function transition(WorkOrderTask $record, string $status): void
    {
        app(TransitionWorkOrderTask::class)->handle($record, $status, auth()->id());
        Notification::make()->title('Tarea actualizada')->success()->send();
    }
    private static function transitionActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
            ViewAction::make(),
            Action::make('start')->label('Iniciar')->icon('heroicon-o-play')->color('warning')->visible(fn (WorkOrderTask $record): bool => $record->status === 'pending')->requiresConfirmation()->action(fn (WorkOrderTask $record) => self::transition($record, 'in_progress')),
            Action::make('finish')->label('Finalizar')->icon('heroicon-o-check-circle')->color('success')->visible(fn (WorkOrderTask $record): bool => $record->status === 'in_progress')->requiresConfirmation()->action(fn (WorkOrderTask $record) => self::transition($record, 'finished')),
            Action::make('reopen')->label('Reabrir')->icon('heroicon-o-arrow-path')->visible(fn (WorkOrderTask $record): bool => in_array($record->status, ['finished', 'cancelled'], true))->requiresConfirmation()->action(fn (WorkOrderTask $record) => self::transition($record, 'pending')),
        ];
    }

    
}
