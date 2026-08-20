<?php

namespace App\Filament\App\Pages;

use BackedEnum;
use Filament\Pages\Page;
use App\Support\SupportAccess;

class Settings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'Ajustes';
    protected static \UnitEnum|string|null $navigationGroup = 'Soporte';

    protected string $view = 'filament.app.pages.settings';

    public static function shouldRegisterNavigation(): bool
    {
        return SupportAccess::canAccess(auth()->user());
    }

    public static function canAccess(): bool
    {
        return SupportAccess::canAccess(auth()->user());
    }
}
