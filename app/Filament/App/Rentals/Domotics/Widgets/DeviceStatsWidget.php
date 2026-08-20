<?php

namespace App\Filament\App\Rentals\Domotics\Widgets;

use App\Enums\DeviceStatus;
use App\Enums\DomoticsEventType;
use App\Models\AccessGrant;
use App\Models\Automation;
use App\Models\Device;
use App\Models\DomoticsEvent;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DeviceStatsWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;
    protected function getStats(): array
    {
        return [
            Stat::make('Dispositivos online', Device::where('status', DeviceStatus::Online)->count())
                ->icon(Heroicon::Signal)
                ->color('success'),
            Stat::make('Accesos hoy', DomoticsEvent::whereDate('created_at', today())->where('event_type', DomoticsEventType::AccessGranted)->count())
                ->icon(Heroicon::Key)
                ->color('primary'),
            Stat::make('PINs activos', AccessGrant::where('is_active', true)->where(function ($q) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            })->count())
                ->icon(Heroicon::Ticket)
                ->color('warning'),
            Stat::make('Automatizaciones activas', Automation::where('is_active', true)->count())
                ->icon(Heroicon::Bolt)
                ->color('info'),
        ];
    }
}
