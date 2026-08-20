<?php

namespace App\Filament\Portal\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Dashboard extends Page
{
    protected string $view = 'filament.portal.pages.dashboard';

    protected static ?string $title = 'Inicio';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?int $navigationSort = 1;

    public function mount(): void {}

    public function getHeading(): string
    {
        return '';
    }
}
