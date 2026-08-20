<?php

namespace App\Filament\App\Resources\TaxistaTickets\Widgets;

use App\Filament\App\Resources\TaxistaTickets\TaxistaTicketResource;
use App\Models\BookingDepartment;
use App\Models\TaxistaTicket;
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

class TaxistaTicketsCalendar extends CalendarWidget
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
            DepartmentManagerAccess::scopeManagedServiceDepartments($query, 'has_tickets_service');
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
            DepartmentManagerAccess::scopeManagedServiceDepartments($query, 'has_tickets_service');
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
        $query = TaxistaTicket::query()
            ->with(['user', 'department'])
            ->where(function ($builder) use ($info): void {
                $builder
                    ->whereBetween('opened_at', [$info->start, $info->end])
                    ->orWhereBetween('due_at', [$info->start, $info->end]);
            });

        $this->scopeTicketQuery($query);

        if (filled($this->departmentId)) {
            $query->where('booking_department_id', (int) $this->departmentId);
        }

        if (filled($this->filterStartDate)) {
            $query->where(function ($q): void {
                $q->whereDate('opened_at', '>=', $this->filterStartDate);
            });
        }

        if (filled($this->filterEndDate)) {
            $query->where(function ($q): void {
                $q->whereDate('opened_at', '<=', $this->filterEndDate)
                    ->orWhereDate('due_at', '<=', $this->filterEndDate);
            });
        }

        return $query
            ->orderBy('opened_at')
            ->get()
            ->map(function (TaxistaTicket $ticket): CalendarEvent {
                $deptColor = $ticket->department?->color;

                $color = filled($deptColor)
                    ? $deptColor
                    : match ($ticket->status) {
                        'abierto' => '#f59e0b',
                        'en_proceso' => '#3b82f6',
                        'resuelto' => '#16a34a',
                        'cerrado' => '#6b7280',
                        default => '#64748b',
                    };

                $startAt = $ticket->opened_at ?? now();
                $endAt = $ticket->due_at ?? $startAt->copy()->addHour();

                $deptLabel = $ticket->department?->name;
                $title = $deptLabel
                    ? "[{$deptLabel}] {$ticket->title}"
                    : $ticket->title;

                return CalendarEvent::make($ticket)
                    ->title($title)
                    ->start($startAt)
                    ->end($endAt)
                    ->backgroundColor($color)
                    ->url(TaxistaTicketResource::getUrl('edit', ['record' => $ticket]), '_self');
            });
    }

    private function scopeTicketQuery(Builder $query): Builder
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return PortalTaxistaContext::scopeTaxistaRecordQuery($query, 'user_id');
        }

        return DepartmentManagerAccess::scopeManagedDepartments($query, column: 'booking_department_id');
    }
}
