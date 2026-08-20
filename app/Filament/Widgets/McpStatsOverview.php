<?php

namespace App\Filament\Widgets;

use Filament\Support\Icons\Heroicon;
use App\Models\McpLog;
use App\Models\Server;
use App\Models\Tool;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
class McpStatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = null;
    protected function getStats(): array
    {
        $todayLogs = McpLog::whereDate('created_at', today())->count();
        $errorRate = McpLog::where('type', 'error')
            ->whereDate('created_at', today())
            ->count();
        $avgDuration = McpLog::whereDate('created_at', today())
            ->avg('duration_ms') ?? 0;
        return [
            Stat::make('Active Servers', Server::where('is_active', true)->count())
                ->description('MCP servers running')
                ->icon(Heroicon::OutlinedServerStack)
                ->color('success'),
            Stat::make('Total Tools', Tool::where('is_active', true)->count())
                ->description('Available tools')
                ->icon(Heroicon::OutlinedWrenchScrewdriver)
                ->color('info'),
            Stat::make('Today\'s Calls', $todayLogs)
                ->description($errorRate.' errors')
                ->icon(Heroicon::OutlinedChartBar)
                ->color($errorRate > 0 ? 'warning' : 'success'),
            Stat::make('Avg Response', number_format($avgDuration, 0).'ms')
                ->description('Average tool duration')
                ->icon(Heroicon::OutlinedClock)
                ->color('gray'),
        ];
    }
}
