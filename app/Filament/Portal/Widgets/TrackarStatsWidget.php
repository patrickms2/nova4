<?php

namespace App\Filament\Widgets;

use App\Models\Taxi\Device;
use App\Models\Taxi\Taxista;
use App\Services\TraccarService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TrackarStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $traccarService = app(TraccarService::class);

        // Get basic counts
        $totalUsers = Taxista::count();
        $totalDevices = Device::count();

        // Ensure session/cookies are available on each request.
        $connected = $traccarService->ensureAuthenticated();

        // Get Traccar data if authenticated
        $traccarDevices = [];
        $onlineDevices = 0;

        if ($connected) {
            $traccarDevices = $traccarService->getDevices();
            $onlineDevices = collect($traccarDevices)
                ->where('status', 'online')
                ->count();
        }

        return [
            Stat::make('Total Taxistas', $totalUsers)
                ->description('Registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Total Dispositivos', count($traccarDevices) ?: $totalDevices)
                ->description('Dispositivos GPS')
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color('info'),

            Stat::make('Dispositivos Online', $onlineDevices)
                ->description('Actualmente activos')
                ->descriptionIcon('heroicon-m-signal')
                ->color($onlineDevices > 0 ? 'success' : 'danger'),

            Stat::make('Traccar Estado', $connected ? 'Conectado' : 'Desconectado')
                ->description($connected ? 'API conectado' : 'Login necesario')
                ->descriptionIcon('heroicon-m-wifi')
                ->color($connected ? 'success' : 'warning'),
        ];
    }
}
