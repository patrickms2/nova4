<?php

namespace App\Filament\Resources;

use Filament\Support\Icons\Heroicon;

use App\Filament\Resources\PublicBookingRequestResource\Pages;
use App\Models\PublicBookingRequest;
use BackedEnum;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema as Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PublicBookingRequestResource extends Resource
{
    protected static ?string $model = PublicBookingRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?string $navigationLabel = 'Solicitudes';

    protected static ?string $modelLabel = 'Solicitud';

    protected static ?string $pluralModelLabel = 'Solicitudes';

    protected static ?string $recordTitleAttribute = 'request_reference';

    protected static string|\UnitEnum|null $navigationGroup = 'Reservas';
    protected static ?string $navigationParentGroup = 'Hub de Cliente';

    protected static ?int $navigationSort = -1;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', 'pending')->count();

        return (string) cache()->remember(
                    static::class . '.navigation-badge',
                    now()->addMinute(),
                    fn () => $count > 0 ? (string) $count : null
                );
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $schema): Form
    {
        return $schema->schema([
            Section::make('Request')
                ->schema([
                    Forms\Components\TextInput::make('request_reference')->disabled(),
                    Forms\Components\TextInput::make('type')->disabled(),
                    Forms\Components\TextInput::make('service_name')->disabled(),
                    Forms\Components\TextInput::make('status')->disabled(),
                    Forms\Components\TextInput::make('assignedAdmin.name')->label('Assigned to')->disabled(),
                    Forms\Components\TextInput::make('assignment_source')->disabled(),
                ])->columns(3),
            Section::make('Customer')
                ->schema([
                    Forms\Components\TextInput::make('customer_name')->disabled(),
                    Forms\Components\TextInput::make('customer_email')->disabled(),
                    Forms\Components\TextInput::make('customer_phone')->disabled(),
                    Forms\Components\Textarea::make('notes')->columnSpanFull()->disabled(),
                ]),
            Section::make('Reservation details')
                ->schema([
                    Forms\Components\TextInput::make('guests')->disabled(),
                    Forms\Components\TextInput::make('rooms')->disabled(),
                    Forms\Components\TextInput::make('passengers')->disabled(),
                    Forms\Components\TextInput::make('participants')->disabled(),
                    Forms\Components\TextInput::make('base_price')->disabled(),
                    Forms\Components\DatePicker::make('check_in_date')->disabled(),
                    Forms\Components\DatePicker::make('check_out_date')->disabled(),
                    Forms\Components\DatePicker::make('reservation_date')->disabled(),
                    Forms\Components\TimePicker::make('reservation_time')->seconds(false)->disabled(),
                    Forms\Components\DateTimePicker::make('pickup_date_time')->disabled(),
                    Forms\Components\DatePicker::make('tour_date')->disabled(),
                    Forms\Components\TimePicker::make('tour_schedule')->seconds(false)->disabled(),
                    Forms\Components\TextInput::make('pickup_address')->disabled(),
                    Forms\Components\TextInput::make('dropoff_address')->disabled(),
                ])->columns(3),
            Section::make('Decision')
                ->schema([
                    Forms\Components\TextInput::make('decidedByAdmin.name')->label('Decided by')->disabled(),
                    Forms\Components\DateTimePicker::make('approved_at')->disabled(),
                    Forms\Components\DateTimePicker::make('cancelled_at')->disabled(),
                    Forms\Components\Textarea::make('decision_notes')->columnSpanFull()->disabled(),
                ]),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('request_reference')->label('Ref')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge()->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'hotel' => 'info',
                        'restaurant' => 'danger',
                        'taxi' => 'warning',
                        'transfer' => 'warning',
                        'tour' => 'primary',
                        'package' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('service_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer_phone')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('base_price')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('assignedAdmin.name')->label('Assigned to')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'hotel' => 'Hotel',
                        'restaurant' => 'Restaurant',
                        'taxi' => 'Taxi',
                        'transfer' => 'Transfer',
                        'tour' => 'Tour',
                        'package' => 'Package',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\Action::make('approve')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (PublicBookingRequest $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (PublicBookingRequest $record): void {
                        $record->approve(Filament::auth()->user());
                    }),
                Actions\Action::make('cancel')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->schema([
                        Forms\Components\Textarea::make('decision_notes')
                            ->label('Reason')
                            ->maxLength(1000),
                    ])
                    ->action(function (PublicBookingRequest $record, array $data): void {
                        $record->cancel(Filament::auth()->user(), $data['decision_notes'] ?? null);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPublicBookingRequests::route('/'),
            'view' => Pages\ViewPublicBookingRequest::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }
}
