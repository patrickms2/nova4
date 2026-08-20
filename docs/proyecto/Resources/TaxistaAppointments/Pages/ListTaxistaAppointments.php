<?php

namespace App\Filament\App\Resources\TaxistaAppointments\Pages;

use App\Enums\CitaStatus;
use App\Filament\App\Resources\TaxistaAppointments\TaxistaAppointmentResource;
use App\Models\TaxistaAppointment;
use App\Support\DepartmentManagerAccess;
use App\Support\PortalTaxistaContext;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Archilex\AdvancedTables\AdvancedTables;

class ListTaxistaAppointments extends ListRecords
{
    use AdvancedTables;

    protected static string $resource = TaxistaAppointmentResource::class;

    protected static ?string $title = 'Citas';

    public function getTitle(): string
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return 'Mis Citas';
        }

        return 'Citas';
    }

    public function getSubheading(): ?string
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return 'Agenda personal de citas.';
        }

        return null;
    }

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

    protected function getHeaderActions(): array
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return [
                CreateAction::make('create_appointment')
                    ->label('Nueva cita')
                    ->color('warning')
                    ->button()
                    ->icon('heroicon-s-plus')
                    ->slideOver()
                    ->modalWidth('4xl')
                    ->modalHeading('Nueva cita')
                    ->modalIcon('heroicon-o-calendar-days')
                    ->modalIconColor('warning')
                    ->modalSubmitAction(fn (Action $action): Action => $action->color('warning'))
                    ->hiddenLabel(),
                Action::make('calendar')
                    ->label('Calendario')
                    ->icon('heroicon-o-calendar-days')
                    ->color('danger')
                    ->button()
                    ->url(TaxistaAppointmentResource::getUrl('calendar')),
                Action::make('kanban')
                    ->label('Kanban')
                    ->icon('heroicon-o-view-columns')
                    ->color('danger')
                    ->button()
                    ->url(TaxistaAppointmentResource::getUrl('kanban')),

            ];
        }

        return [
            CreateAction::make('create_appointment')
                ->label('Nueva cita')
                ->color('warning')
                ->button()
                ->icon('heroicon-o-plus')
                ->slideOver()
                ->modalWidth('4xl')
                ->modalHeading('Nueva cita')
                ->modalIcon('heroicon-o-calendar-days')
                ->modalIconColor('warning')
                ->modalSubmitAction(fn (Action $action): Action => $action->color('warning'))
                ->hiddenLabel()
                ->after(function (self $livewire): void {
                    $livewire->resetTable();
                }),
            Action::make('calendar')
                ->label('Calendario')
                ->icon('heroicon-o-calendar-days')
                ->color('danger')
                ->button()
                ->url(TaxistaAppointmentResource::getUrl('calendar')),
            Action::make('kanban')
                ->label('Kanban')
                ->icon('heroicon-o-view-columns')
                ->color('danger')
                ->button()
                ->url(TaxistaAppointmentResource::getUrl('kanban')),

        ];
    }

    protected function getHeaderWidgets(): array
    {
        return TaxistaAppointmentResource::getWidgets();
    }

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

    private function scopeAppointmentQuery(Builder $query): Builder
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return PortalTaxistaContext::scopeTaxistaRecordQuery($query);
        }

        return DepartmentManagerAccess::scopeManagedDepartments($query, column: 'booking_department_id');
    }
}
