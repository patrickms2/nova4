<?php

namespace App\Filament\App\Resources\TaxistaAppointments\Widgets;

use App\Models\TaxistaAppointment;
use App\Support\DepartmentManagerAccess;
use App\Support\PortalTaxistaContext;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class TaxistaAppointmentStats extends BaseWidget
{
    public ?int $createdByUserId = null;

    protected function getStats(): array
    {
        /** @var object{total:int|string|null, pending:int|string|null, confirmed:int|string|null} $totals */
        $totals = Cache::remember(
            $this->cacheKey(),
            now()->addSeconds(15),
            function (): object {
                return $this->query()
                    ->selectRaw('COUNT(*) as total')
                    ->selectRaw("SUM(CASE WHEN status = 'pendiente' THEN 1 ELSE 0 END) as pending")
                    ->selectRaw("SUM(CASE WHEN status = 'confirmada' THEN 1 ELSE 0 END) as confirmed")
                    ->first() ?? (object)['total' => 0, 'pending' => 0, 'confirmed' => 0];
            },
        );

        return [
            Stat::make('Total citas', (string)((int)($totals->total ?? 0)))
                ->description('Citas registradas')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('gray'),

            Stat::make('Pendientes', (string)((int)($totals->pending ?? 0)))
                ->description('Citas por gestionar')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),
            Stat::make('Confirmadas', (string)((int)($totals->confirmed ?? 0)))
                ->description('Citas confirmadas')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
        ];
    }

    private function query(): Builder
    {
        $query = TaxistaAppointment::query();

        if (PortalTaxistaContext::isPortalPanel()) {
            PortalTaxistaContext::scopeTaxistaRecordQuery($query);
        } else {
            DepartmentManagerAccess::scopeManagedDepartments($query, column: 'booking_department_id');
        }

        if (filled($this->createdByUserId)) {
            $query->where('created_by_user_id', $this->createdByUserId);
        }

        return $query;
    }

    private function cacheKey(): string
    {
        $panelId = \Filament\Facades\Filament::getCurrentPanel()?->getId() ?? 'panel';
        $scopeId = PortalTaxistaContext::isPortalPanel()
            ? 'taxista:' . (string) (PortalTaxistaContext::taxistaUserId() ?? 0)
            : 'user:' . (string) (auth()->id() ?? 0);

        return sprintf('stats:%s:%s:%s:%s', static::class, $panelId, $scopeId, (string)($this->createdByUserId ?? 'all'));
    }
}
