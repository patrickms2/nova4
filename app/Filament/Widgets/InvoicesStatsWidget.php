<?php

namespace App\Filament\Widgets;

use Filament\Support\Icons\Heroicon;

use App\Models\Factura;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InvoicesStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = null;
    protected function getStats(): array
    {
        $count = Factura::count();
        $totalBase = (float) Factura::sum('baseimponible');
        $totalIgic = (float) Factura::sum('impuesto');
        $totalImporte = (float) Factura::sum('importe');

        return [
            Stat::make('Facturas emitidas', $count)
                ->description('Total de facturas')
                ->descriptionIcon(Heroicon::OutlinedDocumentText)
                ->color('primary'),
            Stat::make('Base imponible', number_format($totalBase, 0, ',', '.').' €')
                ->description('Suma de bases')
                ->descriptionIcon(Heroicon::OutlinedReceiptPercent)
                ->color('info'),
            Stat::make('IGIC total', number_format($totalIgic, 0, ',', '.').' €')
                ->description('Impuestos acumulados')
                ->descriptionIcon(Heroicon::OutlinedCalculator)
                ->color('warning'),
            Stat::make('Importe total', number_format($totalImporte, 0, ',', '.').' €')
                ->description('Total facturado')
                ->descriptionIcon(Heroicon::OutlinedCurrencyEuro)
                ->color('success'),
        ];
    }
}
