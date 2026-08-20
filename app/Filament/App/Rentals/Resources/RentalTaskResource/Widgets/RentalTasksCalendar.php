<?php

namespace App\Filament\App\Rentals\Resources\RentalTaskResource\Widgets;

use App\Enums\TaskStatus;
use App\Filament\App\Rentals\Resources\RentalTaskResource;
use App\Models\Task;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

class RentalTasksCalendar extends CalendarWidget
{
    protected CalendarViewType $calendarView = CalendarViewType::DayGridMonth;

    #[Url(as: 'status')]
    public ?string $statusFilter = null;

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

    public function getFilterFormSchema(): array
    {
        return [
            Section::make('Filtros')
                ->schema([
                    Select::make('statusFilter')
                        ->label('Estado')
                        ->options(TaskStatus::class)
                        ->live(),
                    DatePicker::make('filterStartDate')
                        ->label('Desde')
                        ->live(),
                    DatePicker::make('filterEndDate')
                        ->label('Hasta')
                        ->live(),
                ])
                ->columns(3),
        ];
    }

    protected function getEvents(FetchInfo $info): Collection|array
    {
        $query = Task::query()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', $info->start->format('Y-m-d'))
            ->whereDate('due_date', '<=', $info->end->format('Y-m-d'));

        if (filled($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        if (filled($this->filterStartDate)) {
            $query->whereDate('due_date', '>=', $this->filterStartDate);
        }

        if (filled($this->filterEndDate)) {
            $query->whereDate('due_date', '<=', $this->filterEndDate);
        }

        return $query
            ->orderBy('due_date')
            ->get()
            ->map(function (Task $task): CalendarEvent {
                $color = match ($task->status) {
                    'todo' => '#3b82f6',
                    'in_progress' => '#f59e0b',
                    'in_review' => '#8b5cf6',
                    'completed' => '#10b981',
                    'cancelled' => '#ef4444',
                    default => '#64748b',
                };

                $end = $task->due_date->copy()->endOfDay();

                return CalendarEvent::make($task)
                    ->title($task->title)
                    ->start($task->due_date)
                    ->end($end)
                    ->backgroundColor($color)
                    ->url(RentalTaskResource::getUrl('index'));
            });
    }
}
