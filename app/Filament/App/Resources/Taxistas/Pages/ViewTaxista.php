<?php

namespace App\Filament\App\Resources\Taxistas\Pages;

use App\Filament\App\Resources\Taxistas\TaxistaResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTaxista extends ViewRecord
{
    protected static string $resource = TaxistaResource::class;

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return $this->getRecord()->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
