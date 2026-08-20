<?php

namespace App\Filament\RestaurantAdmin\Resources;

use Filament\Support\Icons\Heroicon;

use App\Filament\RestaurantAdmin\Resources\RestaurantBookingResource\Pages;
use App\Models\RestaurantBooking;
use BackedEnum;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema as Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class RestaurantBookingResource extends Resource
{
    protected static ?string $model = RestaurantBooking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|\UnitEnum|null $navigationGroup = 'Reservas';
    protected static ?string $navigationParentGroup = 'Restaurantes';

    protected static ?string $navigationLabel = 'Restaurantes';

    protected static ?int $navigationSort = 7;

    public static function canAccess(): bool
    {
        return true;
        return Filament::auth()->check()
            && ((Filament::auth()->user()->role === 'admin'
                    && Filament::auth()->user()->section === 'restaurant')
                || (Filament::auth()->user()->role === 'sub_admin'
                    && Filament::auth()->user()->section === 'restaurant'));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
        return Filament::auth()->check()
            && ((Filament::auth()->user()->role === 'admin'
                    && Filament::auth()->user()->section === 'restaurant')
                || (Filament::auth()->user()->role === 'sub_admin'
                    && Filament::auth()->user()->section === 'restaurant'));
    }

    /*public static function getEloquentQuery(): Builder
    {
        if (Filament::auth()->user()->role === 'admin') {
            return parent::getEloquentQuery();
        }

        return parent::getEloquentQuery()
            ->whereHas('restaurant', function ($query) {
                $query->where('admin_id', auth()->id());
            });
    }*/

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('booking_id')
                    ->relationship('booking', 'id')
                    ->required()
                    ->searchable()
                    ->preload()
                    // ->multiple()
                    ->native(false),
                Forms\Components\Select::make('restaurant_id')
                    ->relationship('restaurant', 'restaurant_name')
                    ->required()
                    ->searchable()
                    ->preload()
                    // ->multiple()
                    ->native(false),
                /*Forms\Components\Select::make('table_id')
                    ->relationship('table', 'number')
                    ->required()
                    ->searchable()
                    ->preload()
                    // ->multiple()
                    ->native(false),*/

                Forms\Components\DatePicker::make('reservation_date')
                    ->required(),
                Forms\Components\TimePicker::make('reservation_time')
                    ->required(),
                Forms\Components\TextInput::make('number_of_guests')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('duration')
                    ->required()
                    ->numeric()
                    ->default(120),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.first_name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('restaurant.name')
                    ->numeric()->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('table_id')
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
                Tables\Columns\TextColumn::make('reservation_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reservation_time'),
                Tables\Columns\TextColumn::make('order')
                    ->label('Order Details')
                    ->formatStateUsing(function ($state) {
                        $items = json_decode($state, true);

                        if (! $items || ! is_array($items)) {
                            return 'No order';
                        }

                        return collect($items)->map(function ($item) {
                            return "{$item['quantity']} × {$item['name']} ({$item['subtotal']}₺)";
                        })->implode("\n");
                    })
                    ->tooltip(fn ($state) => strip_tags($state))
                    ->wrap(),
                Tables\Columns\TextColumn::make('cost'),
                Tables\Columns\TextColumn::make('numberOfGuests')
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
            'index' => Pages\ListRestaurantBookings::route('/'),
            // 'create' => Pages\CreateRestaurantBooking::route('/create'),
            'view' => Pages\ViewRestaurantBooking::route('/{record}'),
            'edit' => Pages\EditRestaurantBooking::route('/{record}/edit'),
        ];
    }
}
