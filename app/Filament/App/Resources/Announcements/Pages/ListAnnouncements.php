<?php

namespace App\Filament\App\Resources\Announcements\Pages;

use App\Filament\App\Resources\Announcements\AnnouncementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListAnnouncements extends ListRecords
{
    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
            ->modalWidth(Width::FiveExtraLarge),
        ];
    }
}
