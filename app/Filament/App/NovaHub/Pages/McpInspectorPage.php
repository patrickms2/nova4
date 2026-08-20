<?php

namespace App\Filament\App\NovaHub\Pages;

use Filament\Support\Icons\Heroicon;

use Filament\Pages\Page;

class McpInspectorPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlassCircle;

    protected static string|\UnitEnum|null $navigationGroup = 'MCP';
    protected static ?string $navigationParentGroup = 'IA';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Inspector';

    protected static ?string $title = 'MCP Inspector';

    protected static ?string $slug = 'mcp-inspector';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.mcp-inspector';

    public ?int $server = null;

    public function mount(): void
    {
        $this->server = request()->query('server');
    }
}
