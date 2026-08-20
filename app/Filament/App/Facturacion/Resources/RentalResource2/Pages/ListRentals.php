<?php

declare(strict_types=1);

namespace App\Filament\App\Facturacion\Resources\RentalResource2\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\App\Facturacion\Resources\RentalResource2;

class ListRentals extends ListRecords
{
    protected static string $resource = RentalResource2::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
