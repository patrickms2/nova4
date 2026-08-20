<?php

namespace App\Filament\App\NovaHub\Pages;

use Filament\Support\Icons\Heroicon;

use Filament\Pages\Page;

class McpLogViewerPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'MCP';
    protected static ?string $navigationParentGroup = 'IA';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Log Viewer';

    protected static ?string $title = 'MCP Log Viewer';

    protected static ?string $slug = 'mcp-log-viewer';

    protected static ?int $navigationSort = 12;

    protected string $view = 'filament.pages.mcp-log-viewer';
}
