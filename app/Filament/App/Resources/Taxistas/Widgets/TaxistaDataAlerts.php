<?php

namespace App\Filament\App\Resources\Taxistas\Widgets;

use App\Filament\App\Resources\TaxistaTaxis\TaxistaTaxiResource;
use App\Filament\App\Resources\Taxistas\TaxistaResource;
use App\Models\TaxistaTaxi;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class TaxistaDataAlerts extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected int|array|null $columns = [
        'default' => 2,
        'xl' => 5,
    ];

    protected function getStats(): array
    {
        $counts = Cache::remember(
            $this->cacheKey(),
            now()->addSeconds(20),
            function (): array {
                return [
                    'taxistas_without_license' => (int) User::query()
                        ->where('role', 'taxista')
                        ->where('status', true)
                        ->where(function ($query): void {
                            $query->whereNull('licencia')
                                ->orWhere('licencia', '');
                        })
                        ->count(),
                    'taxistas_without_nif' => (int) User::query()
                        ->where('role', 'taxista')
                        ->where('status', true)
                        ->where(function ($query): void {
                            $query->whereNull('nif')
                                ->orWhere('nif', '');
                        })
                        ->count(),
                    'taxistas_without_municipio' => (int) User::query()
                        ->where('role', 'taxista')
                        ->where('status', true)
                        ->whereNull('municipio_id')
                        ->count(),
                    'conductores_without_taxista' => (int) User::query()
                        ->where('role', 'conductor')
                        ->where('status', true)
                        ->whereNull('taxista_id')
                        ->count(),
                    'taxis_without_taxista' => (int) TaxistaTaxi::query()
                        ->where(function ($query): void {
                            $query->whereNull('taxista_user_id')
                                ->orWhere('taxista_user_id', 0);
                        })
                        ->count(),
                ];
            },
        );

        return [
            Stat::make('Taxistas sin licencia', (string) $counts['taxistas_without_license'])
                ->description('Revisar fichas sin licencia')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($counts['taxistas_without_license'] > 0 ? 'danger' : 'success')
                ->url($this->taxistaIndexUrlWithTab('sin_licencia')),

            Stat::make('Taxistas sin NIF', (string) $counts['taxistas_without_nif'])
                ->description('Faltan datos fiscales')
                ->descriptionIcon('heroicon-o-identification')
                ->color($counts['taxistas_without_nif'] > 0 ? 'warning' : 'success')
                ->url($this->taxistaIndexUrlWithTab('sin_nif')),

            Stat::make('Taxistas sin municipio', (string) $counts['taxistas_without_municipio'])
                ->description('Pendientes de asignar municipio')
                ->descriptionIcon('heroicon-o-map-pin')
                ->color($counts['taxistas_without_municipio'] > 0 ? 'warning' : 'success')
                ->url($this->taxistaIndexUrlWithTab('sin_municipio')),

            Stat::make('Conductores sin taxista', (string) $counts['conductores_without_taxista'])
                ->description('Conductores huérfanos')
                ->descriptionIcon('heroicon-o-users')
                ->color($counts['conductores_without_taxista'] > 0 ? 'danger' : 'success')
                ->url(TaxistaResource::getUrl('index')),

            Stat::make('Taxis sin taxista', (string) $counts['taxis_without_taxista'])
                ->description('Taxis pendientes de asignar')
                ->descriptionIcon('heroicon-o-truck')
                ->color($counts['taxis_without_taxista'] > 0 ? 'danger' : 'success')
                ->url($this->taxiIndexUrlWithTab('unassigned')),
        ];
    }

    private function cacheKey(): string
    {
        $panelId = \Filament\Facades\Filament::getCurrentPanel()?->getId() ?? 'panel';

        return sprintf('stats:%s:%s', static::class, $panelId);
    }

    private function taxistaIndexUrlWithTab(string $activeTab): string
    {
        return TaxistaResource::getUrl('index') . '?' . http_build_query([
            'activeTab' => $activeTab,
        ]);
    }

    private function taxiIndexUrlWithTab(string $activeTab): string
    {
        return TaxistaTaxiResource::getUrl('index') . '?' . http_build_query([
            'activeTab' => $activeTab,
        ]);
    }
}
