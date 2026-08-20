<?php

namespace App\Filament\App\NovaHub\Pages;

use Filament\Support\Icons\Heroicon;

use Filament\Pages\Page;

class ToolTesterPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPlayCircle;

    protected static string|\UnitEnum|null $navigationGroup = 'Nova';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Tool Tester';

    protected static ?string $title = 'Tool Tester';

    protected static ?string $slug = 'tool-tester';

    protected static ?int $navigationSort = 11;

    protected string $view = 'filament.pages.tool-tester';

    public ?int $tool = null;

    public function mount(): void
    {
        $this->tool = request()->query('tool');
    }
}
