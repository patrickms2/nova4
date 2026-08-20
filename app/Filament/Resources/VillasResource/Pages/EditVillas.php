<?php

declare(strict_types=1);

namespace App\Filament\Resources\VillaBookingResource\Pages;

use Filament\Support\Icons\Heroicon;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;
use App\Filament\Resources\VillaBookingResource;
use App\Models\Rental;

class EditVillas extends EditRecord
{
    protected static string $resource = VillaBookingResource::class;

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
