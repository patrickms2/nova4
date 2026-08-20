<?php

namespace App\Filament\App\Resources\Bookings\Tables;

use Adultdate\FilamentBooking\Models\Booking\Booking;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn () => Booking::query()->with(['client', 'serviceUser'])->where('booking_user_id', Auth::id()))
            ->columns([
                TextColumn::make('number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('client.phone')
                    ->label('Client phone')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),

                TextColumn::make('client.address')
                    ->label('Client address')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Booking date')
                    ->date()
                    ->toggleable(),
            ])
            ->filters([
                TrashedFilter::make(),

                Filter::make('created_at')
                    ->label('Booking date')
                    ->schema([
                        // keep simple - use Filament datepickers if desired
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query;
                    })
                    ->indicateUsing(function (array $data): array {
                        return [];
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->groupedBulkActions([
                DeleteBulkAction::make()
                    ->action(function (): void {
                        Notification::make()
                            ->title('Now, now, don\'t be cheeky, leave some records for others to play with!')
                            ->warning()
                            ->send();
                    }),
            ])
            ->groups([
                Group::make('created_at')
                    ->label('Booking date')
                    ->date()
                    ->collapsible(),
            ]);
    }
}
