<?php

namespace App\Filament\App\Resources\Bookings\Schemas;

use Adultdate\FilamentBooking\Enums\BookingStatus;
use Adultdate\FilamentBooking\Models\Booking\Booking;
use Adultdate\FilamentBooking\Models\Booking\Client;
use Adultdate\FilamentBooking\Models\Booking\Service;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make()
                            ->schema(static::getDetailsComponents())
                            ->columns(2),

                        Section::make('Booking items')
                            ->afterHeader([
                                Action::make('reset')
                                    ->modalHeading('Are you sure?')
                                    ->modalDescription('All existing items will be removed from the booking.')
                                    ->requiresConfirmation()
                                    ->color('danger')
                                    ->action(fn (Set $set) => $set('items', [])),
                            ])
                            ->schema([
                                static::getItemsRepeater(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 3]),

                // Removed created_at / updated_at display section — not needed in modal
            ])
            ->columns(3);
    }

    /**
     * Determine if the current user may see and edit the booking `status` field.
     */
    public static function canShowStatus(?Booking $record): bool
    {

        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if (is_object($user) && method_exists($user, 'hasRole') && call_user_func([$user, 'hasRole'], 'admin')) {
            return true;
        }

        if ($user->role === 'admin' || $user->role === 'super') {
            return true;
        }

        return false;

    }

    /** @return array<Component> */
    public static function getClientComponents(): array
    {
        return [

        ];
    }

    /** @return array<Component> */
    public static function getDetailsComponents(): array
    {
        return [
            TextInput::make('number')
                ->default('OR-'.random_int(100000, 999999))
                ->disabled()
                ->dehydrated()
                ->required()
                ->maxLength(32)
                ->unique(Booking::class, 'number', ignoreRecord: true),

            TextInput::make('service_date')
                ->default(Auth::id())
                ->dehydrated(),

            TextInput::make('start_time')
                ->default(Auth::id())
                ->dehydrated(),

            TextInput::make('end_time')

                ->default(Auth::id())
                ->dehydrated(),

            Select::make('booking_client_id')
                ->relationship('client', 'name')
                ->searchable()
                ->required()
                ->createOptionForm([
                    Group::make()
                        ->columns(2)
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->required(),
                            TextInput::make('phone')
                                ->maxLength(255)
                                ->required(),
                            TextInput::make('email')
                                ->label('Email address')

                                ->email()
                                ->maxLength(255)
                                ->unique(),

                            TextInput::make('street')
                                ->label('Street address')
                                ->maxLength(255)
                                ->required(),

                            TextInput::make('zip')
                                ->label('Postal code')
                                ->maxLength(20)
                                ->required(),

                            TextInput::make('city')
                                ->maxLength(255)
                                ->required(),

                            TextInput::make('country')
                                ->hidden()
                                ->placeholder('Sweden'),
                        ]),
                ])
                ->createOptionAction(function (Action $action) {
                    return $action
                        ->modalHeading('Create client')
                        ->modalSubmitActionLabel('Create client')
                        ->modalWidth('lg');
                })
                ->createOptionUsing(function (array $data) {
                    $country = $data['country'] ?? null;
                    if (array_key_exists('country', $data)) {
                        unset($data['country']);
                    }

                    $client = Client::create($data);

                    if ($country) {
                        $client->update(['address' => $country]);
                    }

                    return $client->id;
                }),

            Select::make('service_id')
                ->relationship('service', 'name')
                ->searchable()
                ->hidden(),

            Select::make('service_user_id')
                ->label('Service User')
                ->options(User::where('role', 'service')->pluck('name', 'id'))
                ->searchable()
                ->required(),

            TextInput::make('booking_user_id')
                ->hidden()
                ->dehydrated(),

            TextInput::make('admin_id')
                ->hidden()
                ->dehydrated(),

            ToggleButtons::make('status')
                ->inline()
                ->options(BookingStatus::class)
                ->columnSpan('full')
                ->required()
                ->hidden(fn (?Booking $record) => ! static::canShowStatus($record)),

            // Address moved to client create modal; no address field on booking form

            RichEditor::make('notes')
                ->columnSpan('full'),
        ];
    }

    public static function getItemsRepeater(): Repeater
    {
        return Repeater::make('items')
            ->relationship()
            ->table([
                TableColumn::make('Service'),
                TableColumn::make('Quantity')
                    ->width(100),
                TableColumn::make('Unit Price')
                    ->width(110),
            ])
            ->schema([
                Select::make('booking_service_id')
                    ->label('Service')
                    ->options(Service::query()->pluck('name', 'id'))
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, Set $set) => $set('unit_price', Service::find($state)->price ?? 0))
                    ->distinct()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->searchable(),

                TextInput::make('qty')
                    ->label('Quantity')
                    ->numeric()
                    ->default(1)
                    ->required(),

                TextInput::make('unit_price')
                    ->disabled()
                    ->dehydrated()
                    ->numeric()
                    ->required(),
            ])
            ->orderColumn('sort')
            ->defaultItems(1)
            ->hiddenLabel();
    }
}
