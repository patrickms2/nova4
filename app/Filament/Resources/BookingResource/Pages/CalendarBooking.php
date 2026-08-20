<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use Carbon\CarbonImmutable;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema as Form;
use Illuminate\Support\Collection;

class CalendarBooking extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = BookingResource::class;

    protected string $view = 'filament.resources.booking-resource.calendar-booking';

    protected static ?string $title = 'Reservas';

    public array $data = [];

    public ?string $start_date = null;

    public ?string $end_date = null;

    public function mount(): void
    {
        $this->start_date = $this->start_date ?: today()->startOfMonth()->toDateString();
        $this->end_date = $this->end_date ?: today()->toDateString();
        $this->form->fill([
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('start_date')
                    ->label('Desde')
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function ($state): void {
                        $this->start_date = $state ?: today()->startOfMonth()->toDateString();
                    }),
                Forms\Components\DatePicker::make('end_date')
                    ->label('Hasta')
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function ($state): void {
                        $this->end_date = $state ?: today()->toDateString();
                    }),
            ])
            ->columns(2)
            ->statePath('data');
    }

    /**
     * @return array{days: Collection<int, CarbonImmutable>, rows: array<int, array{label:string, key:string, cells: array<int, array{count:int, amount:float}>}>, dailyTotals: array<int, array{count:int, amount:float}>, rangeTotal: array{count:int, amount:float}, currency:string}
     */
    public function getCalendarData(): array
    {
        $start = CarbonImmutable::parse((string) $this->start_date)->startOfDay();
        $end = CarbonImmutable::parse((string) ($this->end_date ?: $this->start_date))->endOfDay();
        if ($end->lessThan($start)) {
            $end = $start->endOfDay();
        }
        // Keep UI sane: cap at 31 days.
        $dayCount = min(31, $start->diffInDays($end) + 1);
        $days = collect(range(0, $dayCount - 1))->map(fn (int $offset): CarbonImmutable => $start->addDays($offset));
        $end = $start->addDays($dayCount - 1)->endOfDay();
        $bookings = Booking::query()
            ->whereBetween('booking_date', [$start, $end])
            ->get([
                'id',
                'booking_date',
                'total_price',
                'booking_type',
            ]);

        $serviceKey = function (Booking $booking): string {
            $name = (string) ($booking->booking_type ?? '');

            return trim($name, '|');
        };

        $rowsIndex = [];
        $dailyTotals = array_fill(0, $days->count(), ['count' => 0, 'amount' => 0.0]);
        $rangeTotal = ['count' => 0, 'amount' => 0.0];
        $currency = 'EUR';

        foreach ($bookings as $booking) {
            if (filled($booking->currency)) {
                $currency = (string) $booking->currency;
            }

            $key = $serviceKey($booking);
            $label = trim((string) ($booking->booking_type ?? 'Tipo'));
            if ($label === '') {
                $label = 'Tipo';
            }
            $rowsIndex[$key] ??= [
                'key' => $key,
                'label' => $label,
                'cells' => array_fill(0, $days->count(), ['count' => 0, 'amount' => 0.0]),
            ];

            $dayIndex = (int) $start->diffInDays(CarbonImmutable::parse($booking->booking_date)->startOfDay());
            if ($dayIndex < 0 || $dayIndex >= $days->count()) {
                continue;
            }

            $amount = (float) ($booking->total_price ?? 0);

            $rowsIndex[$key]['cells'][$dayIndex]['count']++;
            $rowsIndex[$key]['cells'][$dayIndex]['amount'] += $amount;
            $dailyTotals[$dayIndex]['count']++;
            $dailyTotals[$dayIndex]['amount'] += $amount;
            $rangeTotal['count']++;
            $rangeTotal['amount'] += $amount;
        }
        $rows = array_values($rowsIndex);
        usort($rows, fn (array $a, array $b): int => strcmp($a['label'], $b['label']));

        return [
            'days' => $days,
            'rows' => $rows,
            'dailyTotals' => $dailyTotals,
            'rangeTotal' => $rangeTotal,
            'currency' => $currency,
        ];
    }
}
