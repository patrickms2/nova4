<?php

declare(strict_types=1);

namespace App\Filament\App\Rentals\Pages;

use App\Models\RentalReservation;
use Filament\Pages\Page;
use App\Filament\Widgets\RentalCalendarWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use App\Filament\App\Rentals\Rentals;

/**
 * Custom Page "Kalendarz" w sidebarze panelu admina.
 *
 * Pokazuje RentalCalendarWidget (D4) na pelnej stronie zamiast jako widget
 * dashboardowy. Wygodniejsze dla operacji recepcji — pelnoekranowy widok.
 *
 * @see KML-0055 (D5)
 */
class RentalCalendarPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Calendario Alquileres';

    protected static ?string $title = 'Calendario Alquileres';

    protected static string|\UnitEnum|null $navigationGroup = 'Property OS';
    protected static ?string $navigationParentGroup = 'Res. Alquileres';
    protected static ?string $cluster = Rentals::class;

    protected static ?int $navigationSort = 15;

    protected string $view = 'filament.pages.rental-calendar';

    public int $year;

    public int $month;

    public function mount(): void
    {
        $now = Carbon::now();
        $this->year = $now->year;
        $this->month = $now->month;
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
            ->with(['guest', 'rentalProperty'])
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
                    'guest' => $r->guest?->fullName(),
                    'property' => $r->rentalProperty?->name,
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

    public function getColumns(): int|string|array
    {
        return 1;
    }
}
