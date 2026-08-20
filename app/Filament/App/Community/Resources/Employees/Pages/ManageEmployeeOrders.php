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
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;

class ManageEmployeeOrders extends ManageRelatedRecords
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'Ordenes';
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
            ViewAction::make(),
            EditAction::make(),
            DeleteAction::make(),

            Action::make('start')->label('Iniciar')->icon('heroicon-o-play')->color('warning')->visible(fn (WorkOrder $record): bool => $record->status === 'pending')->requiresConfirmation()->action(fn (WorkOrder $record) => self::transition2($record, 'in_progress')),
            Action::make('finish')->label('Finalizar')->icon('heroicon-o-check-circle')->color('success')->visible(fn (WorkOrder $record): bool => $record->status === 'in_progress')->requiresConfirmation()->action(fn (WorkOrder $record) => self::transition2($record, 'finished')),
            Action::make('reopen')->label('Reabrir')->icon('heroicon-o-arrow-path')->visible(fn (WorkOrder $record): bool => in_array($record->status, ['finished', 'cancelled'], true))->requiresConfirmation()->action(fn (WorkOrder $record) => self::transition2($record, 'pending')),
        ];
    }
    protected function getHeaderActions(): array
    {
        return [
            AttachAction::make()->preloadRecordSelect()
            ];
    }
    public static function statuses(): array
    {
        return ['pending' => 'Pendiente', 'in_progress' => 'En curso', 'finished' => 'Finalizada', 'cancelled' => 'Cancelada'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([Select::make('community_id')->label('Comunidad')->relationship('community', 'name')->searchable()->preload()->required(), TextInput::make('code')->label('Código')->unique(ignoreRecord: true), DatePicker::make('work_date')->label('Fecha')->required(), Select::make('status')->label('Estado')->options(self::statuses())->default('pending')->required(), TextInput::make('requester_name')->label('Solicitante'), TextInput::make('requester_phone')->label('Teléfono')->tel(), TextInput::make('reference')->label('Referencia'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->defaultSort('work_date', 'desc')->columns([TextColumn::make('code')->label('Código')->searchable()->sortable(), TextColumn::make('work_date')->label('Fecha')->date()->sortable(), TextColumn::make('reference')->label('Plan')->searchable()->sortable(), TextColumn::make('community.name')->label('Comunidad')->searchable()->sortable(), TextColumn::make('status')->label('Estado')->badge(), TextColumn::make('starter.name')->label('Empleado')->placeholder('Sin iniciar'), TextColumn::make('progress')->label('Progreso')->state(fn (WorkOrder $record): string => $record->completed_tasks_count.'/'.$record->tasks_count), TextColumn::make('incidents_count')->label('Incidencias')->badge()])->filters([SelectFilter::make('community')->relationship('community', 'name')->searchable()->preload(), SelectFilter::make('status')->options(self::statuses()), 
        Filter::make('today')->label('Hoy')->
                query(fn (Builder $query): Builder => $query->whereDate('work_date', today())), Filter::make('has_incidents')->label('Con incidencias')->query(fn (Builder $query): Builder => $query->has('incidents'))])
            ->recordActions(self::transitionActions())
            ->headerActions([
                CreateAction::make('Crear')

                    ->slideOver(),
            ]);
    }
}
