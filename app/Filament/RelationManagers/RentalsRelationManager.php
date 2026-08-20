<?php

declare(strict_types=1);

namespace App\Filament\RelationManagers;

use Filament\Support\Icons\Heroicon;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Models\Rental;
use App\Services\RentalService;

/**
 * Generic RelationManager pokazujacy rezerwacje danego rentable.
 *
 * Wymagania od aplikacji:
 *  1. Model rentable (Motorcycle, Sala, Sprzet, ...) ma metode rentals()
 *     zwracajaca MorphMany. Najprosciej:
 *
 *        use \App\Concerns\IsRentable;
 *
 *  2. Resource Filamentowy aplikacji rejestruje ten manager w getRelations():
 *
 *        public static function getRelations(): array {
 *            return [
 *                \App\Filament\RelationManagers\RentalsRelationManager::class,
 *            ];
 *        }
 *
 * Tab zostanie nazwany "Rezerwacje" i zawiera tabel rezerwacji + akcje
 * statusowe (markPaid, confirm, cancel) wywolujace RentalService.
 *
 * @see KML-0052 (D2)
 */
class RentalsRelationManager extends RelationManager
{
    protected static string $relationship = 'rentals';

    protected static ?string $title = 'Rezerwacje';

    protected static string|BackedEnum|null $icon = Heroicon::OutlinedClipboardDocumentList;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->formatStateUsing(fn (string $state) => substr($state, 0, 8))
                    ->fontFamily('mono'),
                TextColumn::make('name')
                    ->label('Klient')
                    ->description(fn (Rental $r) => $r->email)
                    ->searchable(['name', 'email']),
                TextColumn::make('start_date')
                    ->label('Od')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Do')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Kwota')
                    ->formatStateUsing(fn (int $state, Rental $r) => number_format($state / 100, 2, ',', ' ').' '.$r->currency),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'paid' => 'success',
                        'cancelled' => 'danger',
                        'expired' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Utworzona')
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Oczekuje',
                        'confirmed' => 'Potwierdzona',
                        'paid' => 'Oplacona',
                        'cancelled' => 'Anulowana',
                        'expired' => 'Wygasla',
                    ])
                    ->multiple(),
            ])
            ->recordActions([
                Action::make('markPaid')
                    ->label('Oplacona')
                    ->icon(Heroicon::OutlinedCurrencyDollar)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Rental $r) => in_array($r->status, ['pending', 'confirmed'], true))
                    ->action(function (Rental $r) {
                        app(RentalService::class)->markPaid($r);
                        Notification::make()->title('Oznaczono jako oplacona')->success()->send();
                    }),
                Action::make('cancel')
                    ->label('Anuluj')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Rental $r) => ! in_array($r->status, ['cancelled', 'expired'], true))
                    ->action(function (Rental $r) {
                        app(RentalService::class)->cancel($r);
                        Notification::make()->title('Anulowano')->warning()->send();
                    }),
            ])
            ->defaultSort('start_date', 'desc');
    }

    public function isReadOnly(): bool
    {
        // Tworzenie/edycja rentala odbywa sie przez RentalResource (D1) lub
        // bezposrednio RentalService::book(). Tab w MotorcycleResource sluzy
        // do podgladu + szybkich akcji statusowych.
        return true;
    }
}
