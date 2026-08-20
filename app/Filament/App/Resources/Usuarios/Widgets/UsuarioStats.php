<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Usuarios\Widgets;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

final class UsuarioStats extends BaseWidget
{
    protected function getStats(): array
    {
        /** @var object{total:int|string|null,taxistas:int|string|null,conductores:int|string|null,empleados:int|string|null} $totals */
        $totals = Cache::remember(
            $this->cacheKey(),
            now()->addSeconds(15),
            static function (): object {
                return User::query()
                    ->selectRaw('COUNT(*) as total')
                    ->selectRaw("SUM(CASE WHEN role = 'taxista' THEN 1 ELSE 0 END) as taxistas")
                    ->selectRaw("SUM(CASE WHEN role IN ('conductor', 'conductores') THEN 1 ELSE 0 END) as conductores")
                    ->selectRaw("SUM(CASE WHEN role = 'empleado' THEN 1 ELSE 0 END) as empleados")
                    ->first() ?? (object) [
                        'total' => 0,
                        'taxistas' => 0,
                        'conductores' => 0,
                        'empleados' => 0,
                    ];
            },
        );

        return [
            Stat::make('Usuarios', (string) ((int) ($totals->total ?? 0)))
                ->description('Total de usuarios')
                ->descriptionIcon('heroicon-o-users')
                ->color('gray'),
            Stat::make('Taxistas', (string) ((int) ($totals->taxistas ?? 0)))
                ->description('Usuarios con rol taxista')
                ->descriptionIcon('heroicon-o-truck')
                ->color('info'),
            Stat::make('Conductores', (string) ((int) ($totals->conductores ?? 0)))
                ->description('Usuarios con rol conductor')
                ->descriptionIcon('heroicon-o-identification')
                ->color('warning'),
            Stat::make('Empleados', (string) ((int) ($totals->empleados ?? 0)))
                ->description('Usuarios con rol empleado')
                ->descriptionIcon('heroicon-o-briefcase')
                ->color('success'),
        ];
    }

    private function cacheKey(): string
    {
        $panelId = Filament::getCurrentPanel()?->getId() ?? 'panel';

        return sprintf('stats:%s:%s', static::class, $panelId);
    }
}
