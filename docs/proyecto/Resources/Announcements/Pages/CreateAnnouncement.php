<?php

namespace App\Filament\App\Resources\Announcements\Pages;

use App\Filament\App\Resources\Announcements\AnnouncementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;
}
