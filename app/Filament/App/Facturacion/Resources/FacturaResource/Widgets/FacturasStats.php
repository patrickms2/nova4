<?php

namespace App\Filament\App\Facturacion\Resources\FacturaResource\Widgets;

use App\Filament\App\Facturacion\Resources\FacturaResource\Pages\ListFacturas;
use App\Models\Factura;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class FacturasStats extends BaseWidget
{
    use InteractsWithPageTable;

    protected ?string $pollingInterval = null;

    protected function getTablePage(): string
    {
        return ListFacturas::class;
    }

    protected function getStats(): array
    {
        $orderData = Trend::model(Factura::class)
            ->between(
                start: now()->subYear(),
                end: now(),
            )
            ->perMonth()
            ->count();

        return [
            Stat::make('Facturas', $this->getPageTableQuery()->count())
                ->chart(
                    $orderData
                        ->map(fn (TrendValue $value) => $value->aggregate)
                        ->toArray()
                ),
            Stat::make('Facturas', $this->getPageTableQuery()->whereIn('status', ['open', 'processing'])->count()),
            Stat::make('Total Importe', number_format((float) $this->getPageTableQuery()->sum('importe'), 2)),
        ];
    }
}
