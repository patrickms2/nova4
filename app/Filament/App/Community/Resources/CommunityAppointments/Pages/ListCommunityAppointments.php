<?php

namespace App\Filament\App\Community\Resources\CommunityAppointments\Pages;

use App\Filament\App\Community\Resources\CommunityAppointments\CommunityAppointmentResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommunityAppointments extends ListRecords
{
    protected static string $resource = CommunityAppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('calendar')->label('Calendario')->icon('heroicon-o-calendar-days')->url(CommunityAppointmentResource::getUrl('calendar')),
            Action::make('kanban')->label('Kanban')->icon('heroicon-o-view-columns')->url(CommunityAppointmentResource::getUrl('kanban')),
            CreateAction::make(),
        ];
    }
}
