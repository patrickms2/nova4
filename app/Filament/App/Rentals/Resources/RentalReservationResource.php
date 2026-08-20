<?php

namespace App\Filament\App\Rentals\Resources;

use App\Filament\App\Rentals\Pages\RentalContractSimulator;
use App\Filament\App\Rentals\Rentals;
use App\Filament\App\Rentals\Resources\RentalReservationResource\Pages;
use App\Filament\App\Rentals\Resources\RentalReservationResource\RelationManagers;
use App\Filament\App\Rentals\Resources\RentalReservationResource\Schemas\RentalReservationInfolist;
use App\Models\RentalReservation;
use App\Models\RentalTimelineEvent;
use App\Services\Rental\RentalReservationParser;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class RentalReservationResource extends Resource
{
    protected static ?string $model = RentalReservation::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Reservas';

    protected static ?string $pluralModelLabel = 'Reservas';

    protected static ?string $modelLabel = 'Reserva';

    protected static string|\UnitEnum|null $navigationGroup = 'Property OS';

    protected static ?string $cluster = Rentals::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) cache()->remember(
            static::class.'.upcoming.count',
            now()->addMinute(),
            fn () => RentalReservation::where('check_in', '>=', today())->count()
        );
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Reserva')
                    ->schema([
                        Select::make('rental_property_id')
                            ->relationship('rentalProperty', 'name')
                            ->searchable()
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->name ?? 'Property #'.$record->id)
                            ->preload()
                            ->required(),
                        Select::make('guest_id')
                            ->relationship('guest', 'email')
                            ->searchable(['first_name', 'last_name', 'email', 'phone'])
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->fullName().' — '.($record->email ?? ''))
                            ->preload(),
                        Select::make('person_id')
                            ->label('Persona canónica')
                            ->relationship('person', 'display_name')
                            ->searchable(['display_name', 'first_name', 'last_name', 'email', 'phone'])
                            ->preload()
                            ->helperText('Identidad compartida por Reservas, Property y Access.'),
                        TextInput::make('channel')
                            ->required()
                            ->maxLength(50),
                        TextInput::make('reference_code')
                            ->maxLength(255),
                        DatePicker::make('check_in')
                            ->required()
                            ->native(false),
                        DatePicker::make('check_out')
                            ->required()
                            ->native(false)
                            ->after('check_in'),
                        TextInput::make('adults')
                            ->numeric()
                            ->default(1)
                            ->required(),
                        TextInput::make('children')
                            ->numeric()
                            ->default(0),
                        TextInput::make('amount')
                            ->numeric()
                            ->suffix('€')
                            ->default(0),
                        TextInput::make('channel_commission')
                            ->numeric()
                            ->suffix('€')
                            ->default(0),
                        TextInput::make('management_commission')
                            ->numeric()
                            ->suffix('€')
                            ->default(0),
                        TextInput::make('cleaning_fee')
                            ->numeric()
                            ->suffix('€')
                            ->default(0),
                        TextInput::make('payout')
                            ->numeric()
                            ->suffix('€')
                            ->default(0),
                        Select::make('status')
                            ->options([
                                'confirmed' => 'Confirmada',
                                'pending' => 'Pendiente',
                                'cancelled' => 'Cancelada',
                            ])
                            ->default('confirmed')
                            ->required(),
                        Textarea::make('raw_payload')
                            ->columnSpanFull()
                            ->rows(3)
                            ->nullable(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RentalReservationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('check_in', 'desc')
            ->columns([
                TextColumn::make('reference_code')->label('Reserva')->searchable()->sortable()->placeholder(fn (RentalReservation $record): string => '#'.$record->getKey()),
                TextColumn::make('person.display_name')->label('Huésped / persona')->searchable()->description(fn (RentalReservation $record): ?string => $record->guest?->email)->placeholder(fn (RentalReservation $record): string => $record->guest?->fullName() ?? 'Sin huésped'),
                TextColumn::make('property.name')->label('Propiedad')->searchable()->sortable()->placeholder(fn (RentalReservation $record): string => $record->rentalProperty?->name ?? '—'),
                TextColumn::make('check_in')->label('Entrada')->date('d M Y')->sortable(),
                TextColumn::make('check_out')->label('Salida')->date('d M Y')->sortable(),
                TextColumn::make('nights')->label('Noches')->state(fn (RentalReservation $record): int => $record->nights())->alignEnd(),
                TextColumn::make('channel')->label('Canal')->badge()->sortable(),
                TextColumn::make('amount')->label('Importe')->money('EUR')->sortable()->alignEnd(),
                TextColumn::make('access_readiness')->label('Acceso')->state(fn (RentalReservation $record): string => static::accessReadinessLabel($record))->badge()->color(fn (RentalReservation $record): string => static::accessReadinessColor($record)),
                TextColumn::make('credential')->label('Credencial')->state(fn (RentalReservation $record): string => $record->accessGrants->flatMap->credentials->first()?->maskedValue() ?? ($record->accessGrants->contains(fn ($grant): bool => filled($grant->pin)) ? 'PIN preparado' : '—'))->fontFamily('mono')->toggleable(),
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'confirmed' => 'Confirmada',
                        'pending' => 'Pendiente',
                        'cancelled' => 'Cancelada',
                    ])
                    ->multiple(),
                SelectFilter::make('property')->relationship('property', 'name')->searchable()->preload(),
                SelectFilter::make('channel')
                    ->options([
                        'airbnb' => 'Airbnb',
                        'booking' => 'Booking',
                        'direct' => 'Directo',
                        'guesty' => 'Guesty',
                    ])
                    ->multiple(),
                Filter::make('current')->label('Estancias actuales')->query(fn (Builder $query): Builder => $query->whereDate('check_in', '<=', today())->whereDate('check_out', '>=', today())->where('status', 'confirmed')),
                Filter::make('upcoming')->label('Próximas')->query(fn (Builder $query): Builder => $query->whereDate('check_in', '>', today())->where('status', 'confirmed')),
                Filter::make('completed')->label('Finalizadas')->query(fn (Builder $query): Builder => $query->whereDate('check_out', '<', today())),
            ])
            ->recordActions([
                ViewAction::make()->icon(Heroicon::OutlinedEye),
                EditAction::make()->icon(Heroicon::OutlinedPencilSquare)->slideOver(),
                Action::make('contract')
                    ->label('Simular contrato')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->color('info')
                    ->url(fn (RentalReservation $record): string => RentalContractSimulator::getUrl(['reservation' => $record->getKey()])),
                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->url(fn (RentalReservation $record): ?string => $record->guest?->phone ? 'https://wa.me/'.preg_replace('/[^0-9]/', '', $record->guest->phone) : null)
                    ->openUrlInNewTab()
                    ->hidden(fn (RentalReservation $record): bool => ! $record->guest?->phone),
                Action::make('email')
                    ->label('Email')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->url(fn (RentalReservation $record): ?string => $record->guest?->email ? 'mailto:'.$record->guest->email : null)
                    ->openUrlInNewTab()
                    ->hidden(fn (RentalReservation $record): bool => ! $record->guest?->email),
                DeleteAction::make()->icon(Heroicon::OutlinedTrash),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Crear reserva')
                    ->icon(Heroicon::OutlinedPlus)
                    ->slideOver(),
                Action::make('import')
                    ->label('Importar reservas')
                    ->icon(Heroicon::OutlinedArrowDownOnSquare)
                    ->color('primary')
                    ->form([
                        Select::make('source')
                            ->options([
                                'airbnb' => 'Airbnb email',
                                'booking' => 'Booking email',
                                'bayside' => 'Email Bayside',
                                'guesty' => 'Guesty',
                                'csv' => 'CSV',
                                'json' => 'JSON / raw',
                            ])
                            ->required(),
                        FileUpload::make('file')
                            ->acceptedFileTypes(['text/csv', 'application/json', 'application/pdf', 'text/plain'])
                            ->maxSize(2048)
                            ->nullable(),
                        Textarea::make('raw')
                            ->label('Texto / raw')
                            ->rows(6)
                            ->nullable(),
                    ])
                    ->action(function (array $data): void {
                        $parser = app(RentalReservationParser::class);

                        if ($data['source'] === 'json' && filled($data['raw'])) {
                            $payload = json_decode($data['raw'], true);
                            if (! is_array($payload)) {
                                Notification::make()->title('JSON inválido')->danger()->send();

                                return;
                            }
                            $reservation = $parser->parse($payload);
                        } elseif ($data['source'] === 'csv' && ! empty($data['file'])) {
                            // Placeholder: read first CSV row.
                            $reservation = $parser->parseFromCsv(Storage::path($data['file']));
                        } else {
                            $reservation = $parser->parseFromRaw($data['source'], $data['raw'] ?? '');
                        }

                        if ($reservation instanceof RentalReservation) {
                            RentalTimelineEvent::record($reservation, 'reservation_created', 'Reserva importada', 'Canal: '.($reservation->channel ?? 'desconocido'));
                            Notification::make()->title('Reserva importada')->success()->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->groups([
                'channel',
                'status',
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['payments', 'documents', 'incidents', 'components', 'timelineEvents', 'accessGrants'])
            ->with(['rentalProperty.property', 'property', 'guest', 'person.roles', 'settlement', 'accessGrants.credentials', 'accessGrants.accessPoints']);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PaymentsRelationManager::class,
            RelationManagers\DocumentsRelationManager::class,
            RelationManagers\ComponentsRelationManager::class,
            RelationManagers\IncidentsRelationManager::class,
            RelationManagers\TimelineRelationManager::class,
            RelationManagers\AccessGrantsRelationManager::class,
        ];
    }

    private static function accessReadinessLabel(RentalReservation $reservation): string
    {
        if (! $reservation->person_id) {
            return 'Falta persona';
        }

        if ($reservation->accessGrants->isEmpty()) {
            return 'Falta permiso';
        }

        if (! $reservation->accessGrants->contains(fn ($grant): bool => $grant->credentials->isNotEmpty() || filled($grant->pin))) {
            return 'Falta credencial';
        }

        if (! $reservation->accessGrants->contains(fn ($grant): bool => $grant->accessPoints->isNotEmpty())) {
            return 'Faltan accesos';
        }

        return 'Preparado';
    }

    private static function accessReadinessColor(RentalReservation $reservation): string
    {
        return static::accessReadinessLabel($reservation) === 'Preparado' ? 'success' : 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRentalReservations::route('/'),
            'kanban' => Pages\KanbanRentalReservations::route('/kanban'),
            'calendar' => Pages\CalendarRentalReservations::route('/calendar'),
            'view' => Pages\ViewRentalReservation::route('/{record}'),
        ];
    }
}
