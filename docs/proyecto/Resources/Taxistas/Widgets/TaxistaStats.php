<?php

namespace App\Filament\App\Resources\Taxistas\Widgets;

use App\Models\Taxista;
use App\Models\TaxistaTaxi;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class TaxistaStats extends BaseWidget
{
    protected function getStats(): array
    {
        $todayDate = now()->toDateString();

        /** @var object{active:int|string|null, inactive:int|string|null, created_today:int|string|null} $totals */
        $totals = Cache::remember(
            $this->cacheKey(),
            now()->addSeconds(15),
            function () use ($todayDate): object {
                return Taxista::query()
                    ->selectRaw('SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active')
                    ->selectRaw('SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as inactive')
                    ->selectRaw('SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as created_today', [$todayDate])
                    ->first() ?? (object) ['active' => 0, 'inactive' => 0, 'created_today' => 0];
            },
        );

        $withTaxis = Cache::remember(
            'stats:taxistas:with_taxis',
            now()->addSeconds(15),
            static fn (): int => (int) TaxistaTaxi::query()
                ->whereNotNull('taxista_user_id')
                ->distinct()
                ->count('taxista_user_id'),
        );

        return [
            Stat::make('Activos', (string) ((int) ($totals->active ?? 0)))
                ->description('Taxistas en servicio')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Inactivos', (string) ((int) ($totals->inactive ?? 0)))
                ->description('Taxistas fuera de servicio')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('gray'),

            Stat::make('Con taxis', (string) $withTaxis)
                ->description('Taxistas con taxi vinculado')
                ->descriptionIcon('heroicon-o-truck')
                ->color('info'),

            Stat::make('Nuevos hoy', (string) ((int) ($totals->created_today ?? 0)))
                ->description('Taxistas creados hoy')
                ->descriptionIcon('heroicon-o-plus-circle')
                ->color('warning'),
        ];
    }

    private function cacheKey(): string
    {
        $panelId = \Filament\Facades\Filament::getCurrentPanel()?->getId() ?? 'panel';

        return sprintf('stats:%s:%s', static::class, $panelId);
    }
}
