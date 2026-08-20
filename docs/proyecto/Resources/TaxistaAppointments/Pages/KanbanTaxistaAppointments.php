<?php

namespace App\Filament\App\Resources\TaxistaAppointments\Pages;

use App\Enums\CitaStatus;
use App\Filament\App\Resources\TaxistaAppointments\TaxistaAppointmentResource;
use App\Models\TaxistaAppointment;
use App\Support\DepartmentManagerAccess;
use App\Support\PortalTaxistaContext;
use Asmit\AdvancedKanban\Concerns\InteractsWithKanban;
use Asmit\AdvancedKanban\Columns\KanbanColumn;
use Asmit\AdvancedKanban\Contracts\HasKanban;
use Asmit\AdvancedKanban\Kanban;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class KanbanTaxistaAppointments extends Page implements HasKanban
{
    use InteractsWithKanban;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $resource = TaxistaAppointmentResource::class;

    protected string $view = 'advanced-kanban::index';

    protected static string $model = \App\Models\TaxistaAppointment::class;

    protected static string $recordTitleAttribute = 'title';

    protected static string $recordStatusAttribute = 'status';

    private function makeStatusTab(CitaStatus $status): Tab
    {
        return Tab::make()
            ->label($status->getLabel() ?? ucfirst($status->value))
            ->badge(fn(): int => $this->scopeAppointmentQuery(TaxistaAppointment::query())
                ->where('status', $status->value)
                ->count())
            ->badgeColor($status->getColor())
            ->icon($status->getIcon())
            ->modifyQueryUsing(fn(Builder $query): Builder => $this->scopeAppointmentQuery($query)
                ->where('status', $status->value));
    }

    protected static ?string $title = 'Kanban de citas';

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->label('Todas')
                ->badge(fn(): int => $this->scopeAppointmentQuery(TaxistaAppointment::query())->count()),
            'pendiente' => $this->makeStatusTab(CitaStatus::pendiente),
            'confirmada' => $this->makeStatusTab(CitaStatus::confirmada),
            'finalizada' => $this->makeStatusTab(CitaStatus::finalizada),
            'cancelada' => $this->makeStatusTab(CitaStatus::cancelada),
        ];
    }

    public function getTitle(): string
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return 'Kanban de Mis Citas';
        }

        return 'Kanban de citas';
    }

    public function handleRecordMove(string $newStatus, Model $record): void
    {
        $allowed = ['pendiente', 'confirmada', 'finalizada', 'cancelada'];

        if (!in_array($newStatus, $allowed, true)) {
            return;
        }

        if (PortalTaxistaContext::isPortalPanel()) {
            if (! PortalTaxistaContext::canAccessTaxistaRecord($record)) {
                return;
            }
        } elseif (! DepartmentManagerAccess::canAccessDepartment((int) ($record->booking_department_id ?? 0))) {
            return;
        }

        $record->update(['status' => $newStatus]);
    }

    public function kanban(Kanban $kanban): Kanban
    {
        return $kanban
            ->model(static::$model)
            ->statusField(static::$recordStatusAttribute)
            ->titleField(static::$recordTitleAttribute)
            ->descriptionField('notes')
            ->searchableFields(['title', 'notes'])
            ->enableLoadingIndicator()
            ->recordsPerColumn(15)
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $this->scopeAppointmentQuery($query)
                    ->with(['taxista', 'department', 'tipo'])
                    ->orderByDesc('starts_at');
            })
            ->columns([
                KanbanColumn::make('pendiente')
                    ->label('Pendiente')
                    ->modifyRecordQueryUsing(fn(Builder $query): Builder => $query->where('status', 'pendiente')),

                KanbanColumn::make('confirmada')
                    ->label('Confirmada')
                    ->iconcolor('info')
                    ->modifyRecordQueryUsing(fn(Builder $query): Builder => $query->where('status', 'confirmada')),

                KanbanColumn::make('finalizada')
                    ->label('Finalizada')
                    ->iconcolor('success')
                    ->modifyRecordQueryUsing(fn(Builder $query): Builder => $query->where('status', 'finalizada')),

                KanbanColumn::make('cancelada')
                    ->label('Cancelada')
                    ->iconcolor('danger')
                    ->modifyRecordQueryUsing(fn(Builder $query): Builder => $query->where('status', 'cancelada')),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make('add_appointment')
                ->label('Nueva cita')
                ->icon('heroicon-o-plus')
                ->button()
                ->hiddenLabel()
                ->color('danger')
                ->url(TaxistaAppointmentResource::getUrl('create')),
            Action::make('calendar')
                ->label('Calendario')
                ->icon('heroicon-o-calendar-days')
                ->color('danger')
                ->url(TaxistaAppointmentResource::getUrl('calendar')),

            Action::make('table')
                ->label('Listado')
                ->icon('heroicon-o-table-cells')
                ->color('danger')
                ->url(TaxistaAppointmentResource::getUrl('index')),


        ];
    }

    private function scopeAppointmentQuery(Builder $query): Builder
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return PortalTaxistaContext::scopeTaxistaRecordQuery($query);
        }

        return DepartmentManagerAccess::scopeManagedDepartments($query, column: 'booking_department_id');
    }
}
