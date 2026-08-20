<?php

namespace App\Filament\TourSubAdmin\Resources;

use Filament\Support\Icons\Heroicon;

use App\Filament\TourSubAdmin\Resources\PackageBookingResource\Pages;
use App\Models\PackageBooking;
use App\Models\Promotion;
use App\Models\Tour;
use App\Models\TravelPackage;
use BackedEnum;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema as Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;

class PackageBookingResource extends Resource
{
    protected static ?string $model = PackageBooking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Res. Packages';

    protected static ?string $navigationParentGroup = 'Reservas';

    protected static ?string $navigationLabel = 'Paquetes';

    protected static ?int $navigationSort = 20;

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
        // ->whereHas('tour', function ($query) {
        // $query->where('admin_id', auth()->id());
        // });
    }

    public static function calculateBaseCost($package_id, $number_of_adults, $number_of_children)
    {
        $package = TravelPackage::find($package_id);
        if (! $package) {
            return 0;
        }

        return $package->base_price * ($number_of_adults + $number_of_children);
    }

    public static function applyPromotion($cost, $promotionCode): float
    {
        $promotion = Promotion::where('promotion_code', $promotionCode)
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->where(function ($q) {
                $q->where('applicable_type', 1)
                    ->orWhere('applicable_type', 3);
            })
            ->first();

        if ($promotion) {
            return $cost - ($cost * $promotion->discount_value / 100);
        }

        return -1;
    }

    public static function calculateFinalCost($package_id, $number_of_adults, $number_of_children, $promotion_code = null)
    {
        $base_cost = self::calculateBaseCost($package_id, $number_of_adults, $number_of_children);

        if ($promotion_code) {
            $final = self::applyPromotion($base_cost, $promotion_code);

            return $final !== -1 ? $final : $base_cost;
        }

        return $base_cost;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('tour_id')
                ->default(fn () => Tour::first()->id)
                ->dehydrated(),
            Forms\Components\Select::make('package_id')
                ->relationship('package', 'package_name')->searchable()
                ->required()
                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                    $adults = (int) $get('number_of_adults') ?? 1;
                    $children = (int) $get('number_of_children') ?? 0;
                    $promotion_code = $get('promotion_code');
                    $set('cost', self::calculateFinalCost($state, $adults, $children, $promotion_code));

                    $package = TravelPackage::find($state);
                    if ($package) {
                        $set('agency_id', $package->agency_id);
                    }
                }),

            Forms\Components\Hidden::make('agency_id'),

            TextInput::make('number_of_adults')
                ->numeric()
                ->default(1)
                ->minValue(1)
                ->live()
                ->required()
                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                    $package_id = $get('package_id');
                    $children = (int) $get('number_of_children') ?? 0;
                    $promotion_code = $get('promotion_code');
                    $set('cost', self::calculateFinalCost($package_id, $state, $children, $promotion_code));
                }),

            TextInput::make('number_of_children')
                ->numeric()
                ->default(0)
                ->live()
                ->required()
                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                    $package_id = $get('package_id');
                    $adults = (int) $get('number_of_adults') ?? 1;
                    $promotion_code = $get('promotion_code');
                    $set('cost', self::calculateFinalCost($package_id, $adults, $state, $promotion_code));
                }),

            TextInput::make('promotion_code')
                ->label('Promotion Code')
                ->nullable()
                ->live()
                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                    $package_id = $get('package_id');
                    $adults = (int) $get('number_of_adults') ?? 1;
                    $children = (int) $get('number_of_children') ?? 0;

                    $cost_before = self::calculateBaseCost($package_id, $adults, $children);
                    $final_cost = self::applyPromotion($cost_before, $state);

                    if ($final_cost === -1) {
                        Notification::make()
                            ->title('Invalid or expired promotion code.')
                            ->danger()
                            ->send();

                        $set('cost', $cost_before);
                    } else {
                        $set('cost', $final_cost);
                    }
                }),

            TextInput::make('cost')
                ->numeric()
                ->prefix('EUR')
                ->required()
                ->disabled()
                ->dehydrated(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('package_id')
                    ->numeric()->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('number_of_adults')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('number_of_children')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cost')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_status')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Actions\Action::make('pay')
                    ->label('Pay')
                    ->icon(Heroicon::CurrencyDollar)
                    ->color('success')
                    ->schema([
                        TextInput::make('password')
                            ->label('Password ')
                            ->password()
                            ->required(),
                    ])
                    ->action(function (array $data, $record) {
                        if (! \Hash::check($data['password'], Filament::auth()->user()->password)) {
                            Notification::make()
                                ->title('  Incorrect Password ')
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->update(['payment_status' => 'paid']);

                        Notification::make()
                            ->title('Payed Successfuly  !')
                            ->success()
                            ->send();
                    })
                    ->modalHeading('confirm Paying ')
                    ->modalSubmitActionLabel('Confirm Paying ')
                    ->visible(fn ($record) => $record->payment_status !== 'paid'),

                Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->payment_status !== 'paid'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPackageBookings::route('/'),
            'create' => Pages\CreatePackageBooking::route('/create'),
            'view' => Pages\ViewPackageBooking::route('/{record}'),
            'edit' => Pages\EditPackageBooking::route('/{record}/edit'),
        ];
    }
}
