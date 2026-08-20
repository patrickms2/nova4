<?php

namespace App\Filament\App\Resources\BookingDepartments\Widgets;

use App\Models\BookingDepartment;
use App\Models\TaxistaAppointment;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Support\Collection;

class DepartmentCitasCalendar extends CalendarWidget
{
    protected string $view = 'filament.app.widgets.calendar-simple';

    protected CalendarViewType $calendarView = CalendarViewType::DayGridMonth;

    protected array $options = [
        'editable' => false,
        'selectable' => false,
        'headerToolbar' => [
            'left' => 'prev,next today',
            'center' => 'title',
            'right' => 'dayGridMonth,timeGridWeek,timeGridDay',
        ],
    ];

    public ?BookingDepartment $department = null;

    public function getHeading(): string
    {
        $department = $this->getDepartmentFromContext();
        if ($department) {
            return "Calendario de Citas - {$department->name}";
        }

        return 'Calendario de Citas - Sin departamento';
    }

    protected function getEvents(FetchInfo $info): Collection|array
    {
        $department = $this->getDepartmentFromContext();

        if (!$department) {
            return collect();
        }

        $query = TaxistaAppointment::query()
            ->with(['taxista', 'department'])
            ->where('booking_department_id', $department->id)
            ->where('starts_at', '<', $info->end)
            ->where('ends_at', '>', $info->start);

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

                $taxistaName = $appointment->taxista ? $appointment->taxista->name : 'Sin taxista';
                $title = "[{$taxistaName}] {$appointment->title}";

                // Si no hay fecha de fin, usar la misma fecha de inicio + 1 hora
                $endTime = $appointment->ends_at
                    ? $appointment->ends_at
                    : $appointment->starts_at->copy()->addHour();

                return CalendarEvent::make($appointment)
                    ->title($title)
                    ->start($appointment->starts_at)
                    ->end($endTime)
                    ->backgroundColor($color)
                    ->url(fn() => null);
            });
    }

    public function getEventsJs(array $info): array
    {
        $department = $this->getDepartmentFromContext();

        if (!$department) {
            return [];
        }

        // Obtener todas las citas del departamento
        $query = TaxistaAppointment::query()
            ->with(['taxista', 'department'])
            ->where('booking_department_id', $department->id);

        $events = $query
            ->orderBy('starts_at')
            ->get()
            ->map(function (TaxistaAppointment $appointment): array {
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

                $taxistaName = $appointment->taxista ? $appointment->taxista->name : 'Sin taxista';
                $title = "[{$taxistaName}] {$appointment->title}";

                // Si no hay fecha de fin, usar la misma fecha de inicio + 1 hora
                $endTime = $appointment->ends_at
                    ? $appointment->ends_at
                    : $appointment->starts_at->copy()->addHour();

                return [
                    'id' => $appointment->id,
                    'title' => $title,
                    'start' => $appointment->starts_at->toIso8601String(),
                    'end' => $endTime->toIso8601String(),
                    'backgroundColor' => $color,
                    'extendedProps' => [
                        'model' => TaxistaAppointment::class,
                        'key' => $appointment->id,
                    ],
                ];
            });

        return $events->toArray();
    }

    private function getDepartmentFromContext(): ?BookingDepartment
    {
        // Método 3: Intentar desde $_SERVER como último recurso
        try {
            $requestUri = $_SERVER['HTTP_REFERER'] ?? '';
            if (preg_match('/booking-departments\/(\d+)/', $requestUri, $matches)) {
                $recordId = $matches[1];
                $department = BookingDepartment::find($recordId);
                if ($department) {
                    return $department;
                }
            }
        } catch (\Exception $e) {
            // Silenciosamente fallar
        }
        // Método 1: Intentar desde el Livewire padre
        try {
            if (method_exists($this, 'getLivewire')) {
                $livewire = $this->getLivewire();
                if ($livewire && method_exists($livewire, 'getRecord')) {
                    $record = $livewire->getRecord();
                    if ($record instanceof BookingDepartment) {
                        return $record;
                    }
                }
            }
        } catch (\Exception $e) {
            // Silenciosamente fallar
        }

        // Método 2: Intentar desde la URL usando request()
        try {
            $request = request();
            if ($request) {
                $recordId = $request->route('record');
                if ($recordId) {
                    $department = BookingDepartment::find($recordId);
                    if ($department) {
                        return $department;
                    }
                }
            }
        } catch (\Exception $e) {
            // Silenciosamente fallar
        }


        return null;
    }
}
