<?php

namespace App\Filament\App\NovaHub\Pages;

use Filament\Support\Icons\Heroicon;

use App\Models\McpLog;
use App\Models\Prompt;
use App\Models\Resource;
use App\Models\Server;
use App\Models\Tool;
use Filament\Pages\Page;

class McpDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;
    protected static ?string $navigationLabel = 'Dashboard MCP';
    protected static ?string $title = 'NovaMCP Gestión MCP\'s';

    protected static string|\UnitEnum|null $navigationGroup = 'MCP';
    protected static ?string $navigationParentGroup = 'IA';

    protected static ?string $slug = 'mcp-dashboard';
    protected static ?int $navigationSort = -1;

    protected string $view = 'filament.pages.mcp-dashboard';

    public function getViewData(): array
    {
        return [
            'stats' => [
                'servers' => Server::count(),
                'activeServers' => Server::where('is_active', true)->count(),
                'tools' => Tool::count(),
                'activeTools' => Tool::where('is_active', true)->count(),
                'resources' => Resource::count(),
                'prompts' => Prompt::count(),
            ],
            'recentLogs' => McpLog::with(['server', 'tool'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(),
            'topTools' => Tool::withCount('logs')
                ->orderByDesc('logs_count')
                ->limit(5)
                ->get(),
            'servers' => Server::withCount(['tools', 'resources', 'prompts'])
                ->where('is_active', true)
                ->get(),
        ];
    }
}
