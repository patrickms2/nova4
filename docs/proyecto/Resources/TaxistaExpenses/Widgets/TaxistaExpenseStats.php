<?php

namespace App\Filament\App\Resources\TaxistaExpenses\Widgets;

use App\Enums\TaxistaExpensePaymentType;
use App\Enums\TaxistaExpenseStatus;
use App\Models\TaxistaExpense;
use App\Models\TaxistaExpenseCategory;
use App\Support\PortalTaxistaContext;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class TaxistaExpenseStats extends BaseWidget
{
    protected function getStats(): array
    {
        $todayDate = now()->toDateString();

        /** @var object{pending:int|string|null, recurring:int|string|null, created_today:int|string|null} $totals */
        $totals = Cache::remember(
            $this->cacheKey(),
            now()->addSeconds(15),
            function () use ($todayDate): object {
                return $this->query()
                    ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending', [TaxistaExpenseStatus::Pending->value])
                    ->selectRaw('SUM(CASE WHEN payment_type = ? THEN 1 ELSE 0 END) as recurring', [TaxistaExpensePaymentType::Recurring->value])
                    ->selectRaw('SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as created_today', [$todayDate])
                    ->first() ?? (object) ['pending' => 0, 'recurring' => 0, 'created_today' => 0];
            },
        );

        $configuredTypes = Cache::remember(
            'stats:taxista_expense_categories:active',
            now()->addSeconds(30),
            static fn (): int => (int) TaxistaExpenseCategory::query()
                ->where('is_active', true)
                ->count(),
        );

        return [
            Stat::make('Pendientes', (string) ((int) ($totals->pending ?? 0)))
                ->description('Gastos por revisar')
                ->descriptionIcon('heroicon-o-pause-circle')
                ->color('danger'),

            Stat::make('Recurrentes', (string) ((int) ($totals->recurring ?? 0)))
                ->description('Gastos en cuotas')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color('warning'),

            Stat::make('Tipos activos', (string) $configuredTypes)
                ->description('Categorias configuradas')
                ->descriptionIcon('heroicon-o-cog-6-tooth')
                ->color('info'),

            Stat::make('Nuevos hoy', (string) ((int) ($totals->created_today ?? 0)))
                ->description('Gastos creados hoy')
                ->descriptionIcon('heroicon-o-plus-circle')
                ->color('success'),
        ];
    }

    private function query(): Builder
    {
        return PortalTaxistaContext::scopeTaxistaRecordQuery(TaxistaExpense::query());
    }

    private function cacheKey(): string
    {
        $panelId = \Filament\Facades\Filament::getCurrentPanel()?->getId() ?? 'panel';
        $scopeId = PortalTaxistaContext::isPortalPanel() ? (string) (PortalTaxistaContext::taxistaUserId() ?? 0) : 'all';

        return sprintf('stats:%s:%s:%s', static::class, $panelId, $scopeId);
    }
}
