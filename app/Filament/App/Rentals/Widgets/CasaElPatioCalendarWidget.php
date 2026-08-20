<?php

namespace App\Filament\App\Rentals\Widgets;

use App\Models\RentalExpense;
use App\Models\RentalIncident;
use App\Models\RentalReservation;
use App\Models\Task;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

class CasaElPatioCalendarWidget extends CalendarWidget
{
    protected CalendarViewType $calendarView = CalendarViewType::DayGridMonth;

    #[Url(as: 'type')]
    public ?string $typeFilter = null;

    #[Url(as: 'from')]
    public ?string $filterStartDate = null;
    public ?string $selectedDate = null;

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

    protected function getEvents(FetchInfo $info): Collection|array
    {
        $start = $info->start->format('Y-m-d');
        $end = $info->end->format('Y-m-d');

        $events = collect();

        if (blank($this->typeFilter) || $this->typeFilter === 'reservation') {
            RentalReservation::query()
                ->where('status', 'confirmed')
                ->where('check_in', '<=', $end)
                ->where('check_out', '>=', $start)
                ->get()
                ->each(function (RentalReservation $r) use (&$events): void {
                    $events->push(
                        CalendarEvent::make($r)
                            ->title('Reserva: '.$r->guest?->fullName())
                            ->start($r->check_in)
                            ->end($r->check_out->copy()->addDay())
                            ->backgroundColor('#3b82f6')
                            ->allDay(true)
                    );
                });
        }

        if (blank($this->typeFilter) || $this->typeFilter === 'expense') {
            RentalExpense::query()
                ->whereDate('expense_date', '>=', $start)
                ->whereDate('expense_date', '<=', $end)
                ->get()
                ->each(function (RentalExpense $e) use (&$events): void {
                    $events->push(
                        CalendarEvent::make($e)
                            ->title('Gasto: '.$e->description)
                            ->start($e->expense_date)
                            ->end($e->expense_date)
                            ->backgroundColor('#ef4444')
                            ->allDay(true)
                    );
                });
        }

        if (blank($this->typeFilter) || $this->typeFilter === 'task') {
            Task::query()
                ->whereNotNull('due_date')
                ->whereDate('due_date', '>=', $start)
                ->whereDate('due_date', '<=', $end)
                ->get()
                ->each(function (Task $t) use (&$events): void {
                    $events->push(
                        CalendarEvent::make($t)
                            ->title('Tarea: '.$t->title)
                            ->start($t->due_date)
                            ->end($t->due_date->copy()->endOfDay())
                            ->backgroundColor('#f59e0b')
                    );
                });
        }

        if (blank($this->typeFilter) || $this->typeFilter === 'incident') {
            RentalIncident::query()
                ->whereDate('created_at', '>=', $start)
                ->whereDate('created_at', '<=', $end)
                ->get()
                ->each(function (RentalIncident $i) use (&$events): void {
                    $events->push(
                        CalendarEvent::make($i)
                            ->title('Incidencia: '.$i->title)
                            ->start($i->created_at)
                            ->end($i->created_at->copy()->endOfDay())
                            ->backgroundColor('#8b5cf6')
                    );
                });
        }

        return $events;
    }
}
