<?php

namespace App\Filament\Widgets;

use App\Models\McpLog;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ToolUsageChart extends ChartWidget
{
    protected ?string $heading = 'Tool Usage (Last 7 Days)';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = McpLog::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN type = "error" THEN 1 ELSE 0 END) as errors')
        )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        return [
            'datasets' => [
                [
                    'label' => 'Total Calls',
                    'data' => $data->pluck('total')->toArray(),
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
                'label' => 'Errors',
                'data' => $data->pluck('errors')->toArray(),
                'borderColor' => '#EF4444',
                'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
            ],
            'labels' => $data->pluck('date')->map(fn($d) => date('M j', strtotime($d)))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
