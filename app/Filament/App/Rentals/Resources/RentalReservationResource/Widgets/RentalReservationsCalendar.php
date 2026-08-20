<?php

namespace App\Filament\App\Rentals\Resources\RentalReservationResource\Widgets;

use App\Filament\App\Rentals\Resources\RentalReservationResource;
use App\Models\Property;
use App\Models\RentalReservation;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Illuminate\Support\Carbon;

class RentalReservationsCalendar extends CalendarWidget
{
    protected CalendarViewType $calendarView = CalendarViewType::DayGridMonth;

    #[Url(as: 'property')]
    public ?string $propertyFilter = null;

    #[Url(as: 'status')]
    public ?string $statusFilter = null;


    public int $year;

    public int $month;

    public string $selectedDate;

    public function getAgendaRowsProperty(): Collection
    {
        return RentalReservation::query()
            ->whereDate('check_in', Carbon::parse($this->selectedDate)->toDateString())
            ->with(['property', 'rentalProperty', 'person', 'guest'])
            ->orderBy('check_in')
            ->get();
    }

    public function mount(): void
    {
        $now = Carbon::now();
        $this->year = $now->year;
        $this->month = $now->month;
        $this->selectedDate = request()->string('date')->toString() ?: now()->toDateString();

    }

    public function previousMonth(): void
    {
        $current = Carbon::createFromDate($this->year, $this->month, 1)->subMonthNoOverflow();
        $this->year = $current->year;
        $this->month = $current->month;
    }

    public function nextMonth(): void
    {
        $current = Carbon::createFromDate($this->year, $this->month, 1)->addMonthNoOverflow();
        $this->year = $current->year;
        $this->month = $current->month;
    }

    public function today(): void
    {
        $now = Carbon::now();
        $this->year = $now->year;
        $this->month = $now->month;
    }

    public function getCalendarProperty(): array
    {
        $current = Carbon::createFromDate($this->year, $this->month, 1);
        $start = $current->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $end = $current->copy()->endOfMonth()->endOfWeek(Carbon::MONDAY);

        $reservations = RentalReservation::where('status', 'confirmed')
            ->where(function (Builder $query) use ($start, $end): void {
                $query->whereBetween('check_in', [$start, $end])
                    ->orWhereBetween('check_out', [$start, $end])
                    ->orWhere(function (Builder $q) use ($start, $end): void {
                        $q->where('check_in', '<=', $start)
                            ->where('check_out', '>=', $end);
                    });
            })
            ->with(['guest', 'person', 'property', 'rentalProperty'])
            ->get();

        $weeks = [];
        $week = [];
        $day = $start->copy();

        while ($day <= $end) {
            $dayReservations = $reservations->filter(fn (RentalReservation $r) => $day->greaterThanOrEqualTo($r->check_in) && $day->lessThan($r->check_out));

            $week[] = [
                'date' => $day->copy(),
                'isCurrentMonth' => $day->month === $current->month,
                'isToday' => $day->isToday(),
                'reservations' => $dayReservations->values()->map(fn (RentalReservation $r) => [
                    'id' => $r->id,
                    'guest' => $r->person?->display_name ?? $r->guest?->fullName(),
                    'property' => $r->property?->name ?? $r->rentalProperty?->name,
                    'channel' => $r->channel,
                    'check_in' => $r->check_in->format('d M'),
                    'check_out' => $r->check_out->format('d M'),
                    'isStart' => $day->equalTo($r->check_in),
                    'isEnd' => $day->equalTo($r->check_out->subDay()),
                ])->toArray(),
            ];

            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }

            $day->addDay();
        }

        return [
            'current' => $current,
            'monthName' => $current->isoFormat('MMMM YYYY'),
            'weeks' => $weeks,
        ];
    }


    
    protected array $options = [
        'editable' => false,
        'selectable' => false,
        'firstDay' => 1,
        'headerToolbar' => [
            'left' => 'prev,next today',
            'center' => 'title',
            'right' => 'dayGridMonth,timeGridWeek,listMonth',
        ],
    ];

    public function getFilterFormSchema(): array
    {
        return [
            Section::make('Filtros')->schema([
                Select::make('propertyFilter')
                    ->label('Propiedad')
                    ->options(fn (): array => Property::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->live(),
                Select::make('statusFilter')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'confirmed' => 'Confirmada',
                        'cancelled' => 'Cancelada',
                    ])
                    ->live(),
            ])->columns(2),
        ];
    }

    protected function getEvents(FetchInfo $info): Collection|array
    {
        return RentalReservation::query()
            ->select(['id', 'property_id', 'rental_property_id', 'guest_id', 'person_id', 'reference_code', 'channel', 'check_in', 'check_out', 'status'])
            ->with([
                'property:id,name',
                'rentalProperty:id,name',
                'person:id,display_name',
                'guest:id,first_name,last_name',
                'accessGrants:id,source_type,source_id,pin',
                'accessGrants.credentials:id,name,type,status,valid_from,valid_until',
                'accessGrants.accessPoints:id,name',
            ])
            ->whereDate('check_in', '<=', $info->end->format('Y-m-d'))
            ->whereDate('check_out', '>=', $info->start->format('Y-m-d'))
            ->when(filled($this->propertyFilter), fn (Builder $query): Builder => $query->where('property_id', $this->propertyFilter))
            ->when(filled($this->statusFilter), fn (Builder $query): Builder => $query->where('status', $this->statusFilter))
            ->orderBy('check_in')
            ->get()
            ->map(function (RentalReservation $reservation): CalendarEvent {
                $guest = $reservation->person?->display_name ?? $reservation->guest?->fullName() ?? 'Sin huésped';
                $property = $reservation->property?->name ?? $reservation->rentalProperty?->name ?? 'Sin propiedad';
                $accessReady = $reservation->person_id
                    && $reservation->accessGrants->contains(fn ($grant): bool => ($grant->credentials->isNotEmpty() || filled($grant->pin)) && $grant->accessPoints->isNotEmpty());

                return CalendarEvent::make($reservation)
                    ->title($guest.' · '.$property.' · '.($accessReady ? 'Acceso preparado' : 'Acceso pendiente'))
                    ->start($reservation->check_in)
                    ->end($reservation->check_out)
                    ->allDay(true)
                    ->backgroundColor(match ($reservation->status) {
                        'confirmed' => $accessReady ? '#059669' : '#2563eb',
                        'pending' => '#d97706',
                        'cancelled' => '#dc2626',
                        default => '#64748b',
                    })
                    ->url(RentalReservationResource::getUrl('view', ['record' => $reservation]));
            });
    }
}
