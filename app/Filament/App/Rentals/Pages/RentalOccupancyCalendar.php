<?php

namespace App\Filament\App\Rentals\Pages;

use App\Filament\App\Rentals\Rentals;
use App\Filament\Widgets\RentalCalendarWidget;
use App\Models\RentalReservation;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class RentalOccupancyCalendar extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?string $navigationLabel = 'Calendario de ocupación';

    protected static ?string $title = 'Calendario de ocupación';

    protected static string|\UnitEnum|null $navigationGroup = 'Property OS';

    protected static ?string $cluster = Rentals::class;

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.rental-occupancy-calendar';

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

    public function getWidgets(): array
    {
        return [
            RentalCalendarWidget::class,
        ];
    }
}
