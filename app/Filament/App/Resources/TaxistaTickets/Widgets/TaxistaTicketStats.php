<?php

namespace App\Filament\App\Resources\TaxistaTickets\Widgets;

use App\Models\TaxistaTicket;
use App\Support\DepartmentManagerAccess;
use App\Support\PortalTaxistaContext;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class TaxistaTicketStats extends BaseWidget
{
    protected function getStats(): array
    {
        /** @var object{open:int|string|null, in_progress:int|string|null, resolved:int|string|null, urgent:int|string|null} $totals */
        $totals = Cache::remember(
            $this->cacheKey(),
            now()->addSeconds(15),
            function (): object {
                return $this->query()
                    ->selectRaw("SUM(CASE WHEN status = 'abierto' THEN 1 ELSE 0 END) as open")
                    ->selectRaw("SUM(CASE WHEN status = 'en_proceso' THEN 1 ELSE 0 END) as in_progress")
                    ->selectRaw("SUM(CASE WHEN status = 'resuelto' THEN 1 ELSE 0 END) as resolved")
                    ->selectRaw("SUM(CASE WHEN priority = 'urgente' THEN 1 ELSE 0 END) as urgent")
                    ->first() ?? (object) ['open' => 0, 'in_progress' => 0, 'resolved' => 0, 'urgent' => 0];
            },
        );

        return [
            Stat::make('Abiertos', (string) ((int) ($totals->open ?? 0)))
                ->description('Tickets pendientes')
                ->descriptionIcon('heroicon-o-inbox')
                ->color('warning'),

            Stat::make('En proceso', (string) ((int) ($totals->in_progress ?? 0)))
                ->description('Tickets siendo atendidos')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color('info'),

            Stat::make('Resueltos', (string) ((int) ($totals->resolved ?? 0)))
                ->description('Tickets cerrados con solucion')
                ->descriptionIcon('heroicon-o-check-badge')
                ->color('success'),

            Stat::make('Urgentes', (string) ((int) ($totals->urgent ?? 0)))
                ->description('Tickets de alta prioridad')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }

    private function query(): Builder
    {
        $query = TaxistaTicket::query();

        if (PortalTaxistaContext::isPortalPanel()) {
            return PortalTaxistaContext::scopeTaxistaRecordQuery($query, 'user_id');
        }

        return DepartmentManagerAccess::scopeManagedDepartments($query, column: 'booking_department_id');
    }

    private function cacheKey(): string
    {
        $panelId = \Filament\Facades\Filament::getCurrentPanel()?->getId() ?? 'panel';
        $scopeId = PortalTaxistaContext::isPortalPanel()
            ? 'taxista:' . (string) (PortalTaxistaContext::taxistaUserId() ?? 0)
            : 'user:' . (string) (auth()->id() ?? 0);

        return sprintf('stats:%s:%s:%s', static::class, $panelId, $scopeId);
    }
}
