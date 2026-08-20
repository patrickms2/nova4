<?php

declare(strict_types=1);

namespace App\Filament\App\Facturacion\Resources\RentalResource2\Pages;

use Filament\Support\Icons\Heroicon;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;
use App\Filament\App\Facturacion\Resources\RentalResource2;
use App\Models\Rental;

class EditRental extends EditRecord
{
    protected static string $resource = RentalResource2::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('calendar')
                ->label('Kalendarz')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color('info')
                ->url(function (Rental $record): string {
                    $date = $record->start_date instanceof Carbon
                        ? $record->start_date->format('Y-m-d')
                        : (string) $record->start_date;

                    return url('/admin/rental-calendar-page').'?date='.$date;
                }),
            DeleteAction::make(),
        ];
    }
}
