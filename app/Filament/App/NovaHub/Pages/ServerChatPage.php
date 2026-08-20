<?php

namespace App\Filament\App\NovaHub\Pages;

use Filament\Support\Icons\Heroicon;

use Filament\Pages\Page;
use Filament\Forms\Components\VoiceInput;

class ServerChatPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|\UnitEnum|null $navigationGroup = 'MCP';
    protected static ?string $navigationParentGroup = 'IA';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Server Chat';

    protected static ?string $title = 'Server Chat';

    protected static ?string $slug = 'server-chat';

    protected static ?int $navigationSort = 12;

    protected string $view = 'filament.pages.server-chat';

    public ?int $server = null;

    public function mount(): void
    {
        $this->server = request()->integer('server') ?: null;
    }
}
