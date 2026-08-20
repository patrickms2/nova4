<?php

namespace App\Filament\App\Community\Resources\Communities\Pages;

use App\Filament\App\Community\Resources\Communities\CommunityResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCommunity extends ViewRecord
{
    protected static string $resource = CommunityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('calendar')->label('Calendario de planes')->icon('heroicon-o-calendar-days')->url(fn (): string => CommunityResource::getUrl('calendar', ['record' => $this->getRecord()])),
            EditAction::make(),
        ];
    }
}
