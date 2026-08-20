<?php

namespace App\Filament\Launchpad;

use App\Models\Order;
use Filament\Launchpad\Launchpad\BaseKpiSource;
use Filament\Launchpad\Launchpad\KpiResult;

class FacturasHoyKpi extends BaseKpiSource
{
    // Derivados automaticamente do nome da classe (via BaseKpiSource):
    //   key()   -> "facturas_hoy"    (valor guardado no card, campo kpi_source)
    //   label() -> "Facturas Hoy"  (nome mostrado no Select do card)
    //
    // Sobrepoe SO se precisares de valores diferentes:
    //
    // public static function key(): string
    // {
    //     return 'chave_personalizada';
    // }
    //
    // public function label(): string
    // {
    //     return 'Rotulo personalizado';
    // }
    //
    // Cache do resultado em segundos (null = sem cache; mesmo assim a query
    // corre 1x por request, memoizada):
    //
    public function cacheFor(): ?int
    {
        return 120; // seconds; null = no TTL cache (still memoized once per request)
    }

    //
    // Restringe esta fonte a paineis especificos pelo id (vazio/omitido =
    // visivel em todos os paineis):
    //
    // public function panels(): array
    // {
    //     return ['store'];
    // }

    public function resolve(): KpiResult
    {
        return KpiResult::make(Order::whereDate('created_at', today())->count())
            ->unit('orders')
            ->trend('+3 today', 'success') // success | danger | gray
            ->badge('new');
    }
}
