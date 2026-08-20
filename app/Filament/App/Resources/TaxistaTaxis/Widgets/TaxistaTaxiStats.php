<?php

namespace App\Filament\App\Resources\TaxistaTaxis\Widgets;

use App\Models\TaxistaTaxi;
use App\Support\PortalTaxistaContext;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class TaxistaTaxiStats extends BaseWidget
{
    protected function getStats(): array
    {
        /** @var object{assigned:int|string|null, unassigned:int|string|null, active:int|string|null, inactive:int|string|null} $totals */
        $totals = Cache::remember(
            $this->cacheKey(),
            now()->addSeconds(15),
            function (): object {
                return $this->query()
                    ->selectRaw('SUM(CASE WHEN taxista_user_id IS NOT NULL THEN 1 ELSE 0 END) as assigned')
                    ->selectRaw('SUM(CASE WHEN taxista_user_id IS NULL THEN 1 ELSE 0 END) as unassigned')
                    ->selectRaw("SUM(CASE WHEN status = 'activo' THEN 1 ELSE 0 END) as active")
                    ->selectRaw("SUM(CASE WHEN status = 'baja' THEN 1 ELSE 0 END) as inactive")
                    ->first() ?? (object) ['assigned' => 0, 'unassigned' => 0, 'active' => 0, 'inactive' => 0];
            },
        );

        return [
            Stat::make('Asignados', (string) ((int) ($totals->assigned ?? 0)))
                ->description('Taxis con taxista vinculado')
                ->descriptionIcon('heroicon-o-user')
                ->color('info'),

            Stat::make('Sin asignar', (string) ((int) ($totals->unassigned ?? 0)))
                ->description('Taxis pendientes de asignacion')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('gray'),

            Stat::make('Activos', (string) ((int) ($totals->active ?? 0)))
                ->description('Taxis operativos')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Baja', (string) ((int) ($totals->inactive ?? 0)))
                ->description('Taxis fuera de servicio')
                ->descriptionIcon('heroicon-o-archive-box')
                ->color('danger'),
        ];
    }

    private function query(): Builder
    {
        return PortalTaxistaContext::scopeTaxistaRecordQuery(TaxistaTaxi::query());
    }

    private function cacheKey(): string
    {
        $panelId = \Filament\Facades\Filament::getCurrentPanel()?->getId() ?? 'panel';
        $scopeId = PortalTaxistaContext::isPortalPanel() ? (string) (PortalTaxistaContext::taxistaUserId() ?? 0) : 'all';

        return sprintf('stats:%s:%s:%s', static::class, $panelId, $scopeId);
    }
}
