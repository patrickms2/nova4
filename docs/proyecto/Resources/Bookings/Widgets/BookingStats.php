<?php

namespace App\Filament\App\Resources\Bookings\Widgets;

use Adultdate\FilamentBooking\Models\Booking\Booking;
use App\Filament\App\Resources\Bookings\Pages\ListBookings;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class BookingStats extends BaseWidget
{
    use InteractsWithPageTable;

    protected ?string $pollingInterval = null;

    protected function getTablePage(): string
    {
        return ListBookings::class;
    }

    protected function getStats(): array
    {
        $orderData = Trend::model(Booking::class)
            ->between(
                start: now()->subYear(),
                end: now(),
            )
            ->perMonth()
            ->count();

        return [
            Stat::make('Bookings', $this->getPageTableQuery()->count())
                ->chart(
                    $orderData
                        ->map(fn (TrendValue $value) => $value->aggregate)
                        ->toArray()
                ),
            Stat::make('Complete', $this->getPageTableQuery()->whereIn('status', ['complete'])->count()),
            // Success rate: completed / total bookings (percent)
            Stat::make('Success rate', function () {
                $total = $this->getPageTableQuery()->count();
                $completed = $this->getPageTableQuery()->whereIn('status', ['complete'])->count();

                $rate = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

                return $rate.'%';
            }),
        ];
    }
}
