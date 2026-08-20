<?php

namespace App\Filament\Resources\TaxiTransferBookingResource\Pages;

use Filament\Support\Icons\Heroicon;

use App\Filament\Resources\TaxiTransferBookingResource;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;

class ViewTaxiTransferBooking extends ViewRecord
{
    protected static string $resource = TaxiTransferBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn (): bool => $this->record->status === 'pending')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->approve(Filament::auth()->user());
                }),
            Actions\Action::make('cancel')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->schema([
                    Textarea::make('decision_notes')
                        ->label('Reason')
                        ->maxLength(1000),
                ])
                ->action(function (array $data): void {
                    $this->record->cancel(Filament::auth()->user(), $data['decision_notes'] ?? null);
                }),
        ];
    }
}
