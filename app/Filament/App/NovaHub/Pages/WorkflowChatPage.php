<?php

namespace App\Filament\App\NovaHub\Pages;

use Filament\Support\Icons\Heroicon;

use Filament\Pages\Page;

class WorkflowChatPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|\UnitEnum|null $navigationGroup = 'MCP';
    protected static ?string $navigationParentGroup = 'IA';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationLabel = 'Workflow Chat';

    protected static ?string $title = 'Workflow Chat';

    protected static ?string $slug = 'workflow-chat';

    protected static ?int $navigationSort = 15;

    protected string $view = 'filament.pages.workflow-chat';

    public ?int $workflow = null;

    public ?int $bot = null;

    public function mount(): void
    {
        $this->workflow = request()->integer('workflow') ?: null;
        $this->bot = request()->integer('bot') ?: null;
    }
}
