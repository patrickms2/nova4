<?php

namespace App\Filament\App\Community\Resources\Incidents\Pages;

use App\Filament\App\Community\Resources\Incidents\IncidentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewIncident extends ViewRecord
{
    protected static string $resource = IncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
