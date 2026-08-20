<?php

namespace App\Filament\App\Rentals\Resources\RentalContactResource\Widgets;

use App\Models\RentalContact;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RentalContactStats extends BaseWidget
{
    protected function getStats(): array
    {
        $categories = RentalContact::categories();

        $counts = RentalContact::query()
            ->selectRaw('LOWER(TRIM(category)) as category_key')
            ->selectRaw('COUNT(*) as total')
            ->whereNotNull('category')
            ->groupBy('category_key')
            ->pluck('total', 'category_key');

        return collect($categories)
            ->map(function (string $label, string $key) use ($counts): Stat {
                return Stat::make(
                    $label,
                    (string) ((int) ($counts[$key] ?? 0)),
                )
                    ->description('contactos')
                    ->descriptionIcon('heroicon-o-users')
                    ->color('gray');
            })
            ->values()
            ->all();
    }
}
