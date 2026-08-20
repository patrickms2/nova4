<?php

namespace App\Filament\HotelAdmin\Resources;

use Filament\Support\Icons\Heroicon;

use App\Filament\HotelAdmin\Resources\HotelBookingResource\Pages;
use App\Models\HotelBooking;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Schemas\Schema as Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Actions;
class HotelBookingResource extends Resource
{
    protected static ?string $model = HotelBooking::class;
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string | UnitEnum | null $navigationGroup = 'Res. Hoteles';
    protected static ?string $navigationParentGroup = 'Reservas';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        if (Filament::auth()->user()->role === 'admin') {
            return parent::getEloquentQuery();
        }
        return parent::getEloquentQuery()
        ->whereHas('hotel', function ($query) {
            $query->where('admin_id', auth()->id());
        });
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.first_name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hotel.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hotel_room')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roomType.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('booking.status')
                ->label('Status')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('booking.payment_status')
                ->label('payment status')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('booking.discount_amount')
                ->label('discount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_in_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_out_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('number_of_rooms')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('number_of_guests')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cost')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    public static function canCreate(): bool
    {
        return false;
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHotelBookings::route('/'),
            // 'create' => Pages\CreateHotelBooking::route('/create'),
            'view' => Pages\ViewHotelBooking::route('/{record}'),
            'edit' => Pages\EditHotelBooking::route('/{record}/edit'),
        ];
    }
}
