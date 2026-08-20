<?php

namespace App\Filament\App\Resources\TaxistaAppointments\Pages;

use App\Enums\CitaStatus;
use App\Filament\App\Resources\TaxistaAppointments\TaxistaAppointmentResource;
use App\Filament\App\Resources\TaxistaAppointments\Widgets\TaxistaAppointmentsCalendar;
use App\Models\TaxistaAppointment;
use App\Support\DepartmentManagerAccess;
use App\Support\PortalTaxistaContext;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class CalendarTaxistaAppointments extends Page
{
    protected static string $resource = TaxistaAppointmentResource::class;

    protected static ?string $title = 'Calendario de citas';

    protected string $view = 'filament.app.resources.taxista-appointments.pages.calendar-taxista-appointments';

    public function getTitle(): string
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return 'Calendario de Mis Citas';
        }

        return 'Calendario de citas';
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('nueva')
                ->label('Nueva cita')
                ->color('danger')
                ->icon('heroicon-o-plus')
                ->button()
                ->hiddenLabel()
                ->url(TaxistaAppointmentResource::getUrl('create')),

            Action::make('listado')
                ->label('Listado')
                ->color('danger')
                ->icon('heroicon-o-list-bullet')
                ->url(TaxistaAppointmentResource::getUrl('index')),
            Action::make('kanban')
                ->label('Kanban')
                ->color('danger')
                ->icon('heroicon-o-view-columns')
                ->url(TaxistaAppointmentResource::getUrl('kanban')),
        ];
    }

    /**
     * @return array<class-string<Widget>>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            TaxistaAppointmentsCalendar::class,
        ];
    }
}
