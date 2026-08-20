<?php

namespace App\Filament\App\Resources\TaxistaAppointments\Widgets;

use App\Filament\App\Resources\TaxistaAppointments\TaxistaAppointmentResource;
use App\Models\BookingDepartment;
use App\Models\TaxistaAppointment;
use App\Support\DepartmentManagerAccess;
use App\Support\PortalTaxistaContext;
use Illuminate\Database\Eloquent\Builder;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema as DbSchema;
use Livewire\Attributes\Url;

class TaxistaAppointmentsCalendar extends CalendarWidget
{
    protected string $view = 'filament.app.widgets.calendar-with-filters';

    protected CalendarViewType $calendarView = CalendarViewType::DayGridMonth;

    #[Url(as: 'dept')]
    public ?string $departmentId = null;

    #[Url(as: 'from')]
    public ?string $filterStartDate = null;

    #[Url(as: 'to')]
    public ?string $filterEndDate = null;

    public int $calendarKey = 0;

    public function applyFilters(): void
    {
        $this->calendarKey++;
    }

    protected array $options = [
        'editable' => false,
        'selectable' => false,
        'headerToolbar' => [
            'left' => 'prev,next today',
            'center' => 'title',
            'right' => 'dayGridMonth,timeGridWeek,timeGridDay',
        ],
    ];

    /** @return array<int, string> */
    public function getDepartmentOptions(): array
    {
        if (! DbSchema::hasTable('booking_departments')) {
            return [];
        }

        $query = BookingDepartment::query()
            ->where('is_active', true)
            ->orderBy('name');

        if (! PortalTaxistaContext::isPortalPanel()) {
            DepartmentManagerAccess::scopeManagedServiceDepartments($query, 'has_meetings_service');
        }

        return $query->pluck('name', 'id')->all();
    }

    /** @return array<int, array{name: string, color: string}> */
    public function getDepartmentColors(): array
    {
        if (! DbSchema::hasTable('booking_departments')) {
            return [];
        }

        $query = BookingDepartment::query()
            ->where('is_active', true)
            ->orderBy('name');

        if (! PortalTaxistaContext::isPortalPanel()) {
            DepartmentManagerAccess::scopeManagedServiceDepartments($query, 'has_meetings_service');
        }

        return $query
            ->get(['id', 'name', 'color'])
            ->map(fn (BookingDepartment $dept): array => [
                'name' => (string) $dept->name,
                'color' => $dept->color ?: '#64748b',
            ])
            ->all();
    }

    protected function getEvents(FetchInfo $info): Collection|array
    {
        $query = TaxistaAppointment::query()
            ->with(['taxista', 'department'])
            ->where('starts_at', '<', $info->end)
            ->where(function ($query) use ($info) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', $info->start);
            });

        $this->scopeAppointmentQuery($query);

        if (filled($this->departmentId)) {
            $query->where('booking_department_id', (int) $this->departmentId);
        }

        if (filled($this->filterStartDate)) {
            $query->whereDate('starts_at', '>=', $this->filterStartDate);
        }

        if (filled($this->filterEndDate)) {
            $query->whereDate('starts_at', '<=', $this->filterEndDate);
        }

        return $query
            ->orderBy('starts_at')
            ->get()
            ->map(function (TaxistaAppointment $appointment): CalendarEvent {
                $deptColor = $appointment->department?->color;

                $color = filled($deptColor)
                    ? $deptColor
                    : match ($appointment->status) {
                        'pendiente' => '#f59e0b',
                        'confirmada' => '#3b82f6',
                        'finalizada' => '#16a34a',
                        'cancelada' => '#6b7280',
                        default => '#64748b',
                    };

                $deptLabel = $appointment->department?->name;
                $title = $deptLabel
                    ? "[{$deptLabel}] {$appointment->title}"
                    : $appointment->title;

                // Si no hay fecha de fin, usar la misma fecha de inicio + 1 hora
                $endTime = $appointment->ends_at 
                    ? $appointment->ends_at 
                    : $appointment->starts_at->copy()->addHour();

                return CalendarEvent::make($appointment)
                    ->title($title)
                    ->start($appointment->starts_at)
                    ->end($endTime)
                    ->backgroundColor($color)
                    ->url(TaxistaAppointmentResource::getUrl('edit', ['record' => $appointment]), '_self');
            });
    }

    private function scopeAppointmentQuery(Builder $query): Builder
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return PortalTaxistaContext::scopeTaxistaRecordQuery($query);
        }

        return DepartmentManagerAccess::scopeManagedDepartments($query, column: 'booking_department_id');
    }
}
