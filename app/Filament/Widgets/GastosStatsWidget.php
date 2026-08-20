<?php

namespace App\Filament\Widgets;

use App\Models\Gasto;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GastosStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $monthQuery = Gasto::query()->where('type', 'expense')
            ->whereYear('fecha', now()->year)
            ->whereMonth('fecha', now()->month);

        $count = (int) $monthQuery->clone()->count();
        $total = (float) $monthQuery->clone()->sum('total');
        $average = $count > 0 ? round($total / $count, 2) : 0;
        $thisMonth = (float) $total;

        return [
            Stat::make('Gastos registrados', $count)
                ->description('Gastos del mes actual')
                ->descriptionIcon(Heroicon::OutlinedReceiptPercent)
                ->color('primary'),
            Stat::make('Total periodo', number_format($total, 2, ',', '.').' €')
                ->description('Suma total del mes')
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->color('danger'),
            Stat::make('Gasto medio', number_format($average, 2, ',', '.').' €')
                ->description('Media por gasto')
                ->descriptionIcon(Heroicon::OutlinedCalculator)
                ->color('warning'),
            Stat::make('Este mes', number_format($thisMonth, 2, ',', '.').' €')
                ->description('Gastos en este mes')
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->color('success'),
        ];
    }
}
