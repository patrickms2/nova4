<?php

namespace App\Filament\App\Community\Resources\WorkOrders;

use App\Actions\Community\TransitionWorkOrder;
use App\Models\WorkOrder;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
class WorkOrderResource extends Resource 
{
    
    protected static ?string $model = WorkOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Órdenes';

    protected static ?string $modelLabel = 'Orden';

    protected static ?string $pluralModelLabel = 'Órdenes';
    protected static string|\UnitEnum|null $navigationGroup = 'Mantenimiento';
    protected static ?string $navigationParentGroup = 'Órdenes';
    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'Mantenimiento';
    }
    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([Pages\ViewWorkOrder::class, 
        Pages\ManagePlans::class,
        Pages\CalendarCommunity::class,
        Pages\ManageTasks::class,
        Pages\ManageIncidents::class,

        ]);
    }
    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Orden')->schema([
            Select::make('community_id')->label('Comunidad')->relationship('community', 'name')->preload()->required(), 
            Select::make('community_plan_id')->label('Plan')->relationship('plan', 'name')->preload(), 
            Select::make('user_id')->label('Empleado')->relationship('employee', 'name')->preload(), 
            TextInput::make('code')->label('Código')->required(), 
            DatePicker::make('work_date')->label('Fecha')->required(), 
            Select::make('status')->label('Estado')->options(self::statuses())->default('pending')->required(), 
            TextInput::make('requester_name')->label('Solicitante'), 
            TextInput::make('requester_phone')->label('Teléfono')->tel(), 
            TextInput::make('reference')->label('Referencia'),
        ])->columns(2)]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Orden')->schema([
                TextEntry::make('code')->label('Código'), 
                TextEntry::make('community.name')->label('Comunidad'), 
                TextEntry::make('plan.name')->label('Plan'), 
                TextEntry::make('status')->label('Estado')->badge(), 
            ])
            ->columns(2),
Section::make('Empleado')->schema([
                TextEntry::make('employee.name')->label('Asignado'), 
                TextEntry::make('starter.name')->label('Iniciada por')->placeholder('Sin iniciar'), 
                TextEntry::make('finisher.name')->label('Finalizada por')->placeholder('—'), 
            ])
            ->columns(3),
Section::make('Fechas')->schema([
                TextEntry::make('work_date')->label('Asignada')->date(), 
                TextEntry::make('started_at')->label('Inicio')->dateTime()->placeholder('—'), 
                TextEntry::make('finished_at')->label('Fin')->dateTime()->placeholder('—'), 
            ])
            ->columns(3),
            Section::make('Resumen')->schema([
                                TextEntry::make('employee_count')->label('Empleados'), 

                TextEntry::make('tasks_count')->label('Tareas'), 
                TextEntry::make('completed_tasks_count')->label('Completadas'), 
                TextEntry::make('incidents_count')->label('Incidencias'), 
                TextEntry::make('order_comments_count')->label('Comentarios'), 
                TextEntry::make('photos_count')->label('Fotos')])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('work_date', 'desc')
        ->columns([
            TextColumn::make('code')->label('Código')->searchable()->sortable(), 
            TextColumn::make('work_date')->label('Fecha')->date()->sortable(), 
            TextColumn::make('community.name')->label('Comunidad')->searchable()->sortable(), 
            TextColumn::make('employee.name')->label('Empleado'), 

            TextColumn::make('status')->label('Estado')->badge(), 
            TextColumn::make('starter.name')->label('Empleado')->placeholder('Sin iniciar'), 
            TextColumn::make('progress')->label('Progreso')->state(fn (WorkOrder $record): string => $record->completed_tasks_count.'/'.$record->tasks_count), 
            TextColumn::make('incidents_count')->label('Incidencias')->badge()])
        ->filters([SelectFilter::make('community')->relationship('community', 'name')->searchable()->preload(), SelectFilter::make('status')->options(self::statuses()), Filter::make('today')->label('Hoy')->query(fn (Builder $query): Builder => $query->whereDate('work_date', today())), Filter::make('has_incidents')->label('Con incidencias')->query(fn (Builder $query): Builder => $query->has('incidents'))])
        ->recordActions(self::transitionActions())
        ->groups([
            'Comunidades' => 'community.name',
            'Empleado' =>'starter.name',
            'Estado' =>'status',
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['orderComments','plan','community','employee', 'starter', 'finisher'])->withCount(['orderComments','employee','tasks', 'tasks as completed_tasks_count' => fn (Builder $query) => $query->where('status', 'completed'), 'incidents', 'comments', 'photos']);
    }

    public static function getRelations(): array
    {
        return [RelationManagers\TasksRelationManager::class, RelationManagers\IncidentsRelationManager::class, RelationManagers\CommentsRelationManager::class, RelationManagers\PhotosRelationManager::class];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListWorkOrders::route('/'), 'create' => Pages\CreateWorkOrder::route('/create'), 'view' => Pages\ViewWorkOrder::route('/{record}'), 
        'edit' => Pages\EditWorkOrder::route('/{record}/edit'),
                'plan' => Pages\ManagePlans::route('/{record}/plan'), 
        'tasks' => Pages\ManageTasks::route('/{record}/tasks'),
        'incidents' => Pages\ManageIncidents::route('/{record}/incidents'),
        'calendar' => Pages\CalendarCommunity::route('/{record}/calendar'),
        ];
    }

    public static function statuses(): array
    {
        return ['pending' => 'Pendiente', 'in_progress' => 'En curso', 'resolved' => 'Resuelta', 'cancelled' => 'Cancelada'];
    }

    private static function transitionActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
            ViewAction::make(),
            Action::make('start')->label('Iniciar')->icon('heroicon-o-play')->color('warning')->visible(fn (WorkOrder $record): bool => $record->status === 'pending')->requiresConfirmation()->action(fn (WorkOrder $record) => self::transition($record, 'in_progress')),
            Action::make('finish')->label('Finalizar')->icon('heroicon-o-check-circle')->color('success')->visible(fn (WorkOrder $record): bool => $record->status === 'in_progress')->requiresConfirmation()->action(fn (WorkOrder $record) => self::transition($record, 'finished')),
            Action::make('reopen')->label('Reabrir')->icon('heroicon-o-arrow-path')->visible(fn (WorkOrder $record): bool => in_array($record->status, ['finished', 'cancelled'], true))->requiresConfirmation()->action(fn (WorkOrder $record) => self::transition($record, 'pending')),
        ];
    }

    public static function transition(WorkOrder $record, string $status): void
    {
        app(TransitionWorkOrder::class)->handle($record, $status, auth()->id());
        Notification::make()->title('Orden actualizada')->success()->send();
    }
}
