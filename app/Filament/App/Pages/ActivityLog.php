<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Resources\ActivitylogResource;
use App\Support\ActivityAccess;
use Filament\Pages\Page;

class ActivityLog extends Page
{
    protected string $view = 'filament.app.pages.activity-log';

    public function mount(): void
    {
        $this->redirect(ActivitylogResource::getUrl(), navigate: true);
    }

    public static function canAccess(): bool
    {
        return ActivityAccess::canViewAny(auth()->user());
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
