<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Support\Icons\Heroicon;

use App\Enums\StepPosition;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use App\Contracts\PricingStrategyInterface;
use App\Filament\Resources\VillaBookingResource\Pages;
use App\Models\Admin\VillaReservation as Rental;

use App\Models\RentalType;
use App\Services\RentalService;

use App\Enums\TablerIcon;

use Exception;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Enums\IconSize;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use LogicException;
use Random\RandomException;
use App\Traits\Filament\CanCustomizeHeaderActions;
use App\Traits\Filament\CanCustomizeHeaderWidgets;
use App\Traits\Filament\CanCustomizeTabs;
use App\Traits\Filament\CanCustomizeSteps;


/**
 * Filament 5 Resource — lista rezerwacji + filtry + akcje statusowe.
 *
 * Port: blizne-art-cms ReservationResource z adaptacja:
 *  - rentable polimorficznie (zamiast time_slot)
 *  - status flow przez RentalService (markPaid / confirm / cancel)
 *  - kwota w grosze (total_amount / 100)
 *
 * @see KML-0051 (D1)
 */
class VillaBookingResource extends Resource
{
    use CanCustomizeHeaderActions;
    use CanCustomizeHeaderWidgets;
    use CanCustomizeSteps;

    protected static ?string $model = Rental::class;

        public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $modelLabel = 'Reserva';

    protected static string|\UnitEnum|null $navigationGroup = 'Property OS';
    protected static ?string $cluster = Rentals::class;
    protected static ?string $navigationLabel = 'Reservas';
    protected static ?string $pluralModelLabel = 'Reservas';

    protected static ?int $navigationSort = 10;
    protected static bool $canCreateAnother = false;

    public ?Node $node = null;

    protected static function getDefaultSteps(): array
    {
        return [
            Step::make('Information')
                ->label('Information')
                ->icon(TablerIcon::InfoCircle)
                ->completedIcon(TablerIcon::Check)
                ->columns([
                    'default' => 1,
                    'sm' => 3,
                    'md' => 3,
                ])
                ->schema([
                    ...self::settlementSection(),
                    ...self::resourceSection(),

                ]),
            Step::make('Information2')
                ->label('Information2')
                ->icon(TablerIcon::InfoCircle)
                ->completedIcon(TablerIcon::Check)
                ->columns([
                    'default' => 1,
                    'sm' => 1,
                    'md' => 1,
                ])
                ->columnSpanFull()
                ->schema([


                    Section::make('Fechas')
                        ->columnSpanFull()
                        ->schema([
                            DateTimePicker::make('start_at')
                                ->label('Inicio')
                                ->required()
                                ->native(false)
                                ->seconds(false)
                                ->minutesStep(15)
                                ->displayFormat('d.m.Y H:i')
                                ->default(fn () => now()->startOfDay()->setHour((int) config('rental.default_pickup_hour', 10)))
                                // KML-0061: fallback gdy rekord ma start_date ale start_at=NULL
                                // (legacy/frontend flow tworzacy rezerwacje bez datetime).
                                ->formatStateUsing(function ($state, ?Rental $record) {
                                    if ($state) {
                                        return $state;
                                    }
                                    if ($record?->start_date) {
                                        $h = (int) config('rental.default_pickup_hour', 10);
                                        $d = $record->start_date instanceof \Illuminate\Support\Carbon
                                            ? $record->start_date
                                            : \Illuminate\Support\Carbon::parse((string) $record->start_date);

                                        return $d->copy()->setTime($h, 0, 0);
                                    }

                                    return $state;
                                })
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcTotalAmount($get, $set)),
                            DateTimePicker::make('end_at')
                                ->label('Fin')
                                ->required()
                                ->native(false)
                                ->seconds(false)
                                ->minutesStep(15)
                                ->displayFormat('d.m.Y H:i')
                                ->after('start_at')
                                ->default(fn () => now()->startOfDay()->setHour((int) config('rental.default_pickup_hour', 10)))
                                // KML-0061: fallback identyczny jak dla start_at.
                                ->formatStateUsing(function ($state, ?Rental $record) {
                                    if ($state) {
                                        return $state;
                                    }
                                    if ($record?->end_date) {
                                        $h = (int) config('rental.default_pickup_hour', 10);
                                        $d = $record->end_date instanceof \Illuminate\Support\Carbon
                                            ? $record->end_date
                                            : \Illuminate\Support\Carbon::parse((string) $record->end_date);

                                        return $d->copy()->setTime($h, 0, 0);
                                    }

                                    return $state;
                                })
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcTotalAmount($get, $set)),
                            TextInput::make('qty')
                                ->label('Cant.')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcTotalAmount($get, $set)),
                            TextInput::make('total_amount')
                                ->label('Total')
                                ->numeric()
                                ->required()
                                ->prefix('EUR')
                                ->step(0.01)
                                ->minValue(0)
                                ->helperText('Kwota w PLN (np. 199,00). Zapis w groszach realizowany automatycznie.')
                                ->formatStateUsing(fn ($state) => $state !== null && $state !== '' ? number_format(((int) $state) / 100, 2, '.', '') : null)
                                ->dehydrateStateUsing(fn ($state) => $state !== null && $state !== '' ? (int) round(((float) str_replace(',', '.', (string) $state)) * 100) : 0),
                            TextInput::make('currency')
                                ->label('Waluta')
                                ->maxLength(3)
                                ->default(fn () => config('rental.currency', 'EUR')),
                        ])
                        ->columns(5),
                            ]),
            Step::make('Information3')
                ->label('Information3')
                ->icon(TablerIcon::InfoCircle)
                ->completedIcon(TablerIcon::Check)
                ->columns([
                    'default' => 1,
                    'sm' => 4,
                    'md' => 4,
                ])
                ->schema([
            Section::make('Klient')
                ->schema([
                    TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(255),
                    TextInput::make('phone')
                        ->label('Telefon')
                        ->tel()
                        ->maxLength(50),
                    Toggle::make('gdpr_consent')
                        ->label('Zgoda RODO')
                        ->inline(false),
                ])
                ->columns(2),

            Section::make('Status i płatność')
                ->schema([
                    Select::make('status')
                        ->label('Estado')
                        ->options([
                            'pending' => 'pending',
                            'confirmed' => 'confirmed',
                            'paid' => 'paid',
                            'cancelled' => 'cancelled',
                            'expired' => 'expired',
                        ])
                        ->required()
                        ->default('pending')
                        // KML-0058: live, by sekcja "Rozliczenie" reagowala na zmiane statusu.
                        ->live(),
                    TextInput::make('payment_order_id')
                        ->label('ID Pago')
                        ->maxLength(255),
                ])
                ->columns(2),

            Section::make('Dodatkowe')
                ->schema([
                    Textarea::make('message')
                        ->label('message')
                        ->rows(3)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                ])
                ->collapsed()
                ->collapsible(),
        ]),

        ];
    }
    protected static array $customSteps = [];

    public static function registerCustomSteps(StepPosition $position, Step ...$customSteps): void
    {
        static::$customSteps[$position->value] = array_merge(static::$customSteps[$position->value] ?? [], $customSteps);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make(array_merge(
                static::$customSteps['before'] ?? [],
                    self::getDefaultSteps(),
                static::$customSteps['after'] ?? []
            ))
                ->columnSpanFull()
                ->nextAction(fn (Action $action) => $action->tooltip(fn () => $action->getLabel())->iconButton()->iconSize(IconSize::ExtraLarge)->icon(TablerIcon::ArrowRight))
                ->previousAction(fn (Action $action) => $action->tooltip(fn () => $action->getLabel())->iconButton()->iconSize(IconSize::ExtraLarge)->icon(TablerIcon::ArrowLeft))
                ->submitAction(new HtmlString(Blade::render(<<<'BLADE'
                        <x-filament::icon-button
                            type="submit"
                            iconSize="xl"
                            icon="tabler-plus"
                            tooltip="{{ trans('admin/server.create') }}"
                        >
                            {{ trans('admin/server.create') }}
                        </x-filament::icon-button>
                    BLADE))),

        ]);
    }

    /**
     * Sekcja "Rozliczenie" — widoczna tylko w edycji, pokazuje:
     *  - paid_amount (kwota faktycznie pobrana online)
     *  - total_amount (live, reaguje na zmiane motocykla)
     *  - balance = total_amount - paid_amount (live calc)
     *
     * Sekcja jest no-op gdy formularz jest w trybie create (brak record-u
     * i brak paid_amount). Wartosci formatowane jako "X,XX zl".
     *
     * @return array<int, \Filament\Schemas\Components\Component>
     *
     * @see KML-0047 (rozszerzenie: rozliczenie w biurze)
     */
    public static function settlementSection(): array
    {
        return [
            Section::make('Pagos')
                ->visible(fn (?Rental $record): bool => $record !== null)
                ->schema([
                    TextEntry::make('settlement_paid')
                        ->label('paid')
                        ->state(function (?Rental $record): string {
                            $paid = (int) ($record->paid_amount ?? 0);

                            return number_format($paid / 100, 2, ',', ' ').' '.($record?->currency ?? config('rental.currency', 'EUR'));
                        }),
                    TextEntry::make('settlement_total')
                        ->label('total')
                        ->live()
                        ->state(function (Get $get, ?Rental $record): string {
                            $total = self::resolveTotalAmountInGrosze($get, $record);

                            return number_format($total / 100, 2, ',', ' ').' '.($record?->currency ?? config('rental.currency', 'PLN'));
                        }),
                    TextEntry::make('settlement_balance')
                        ->label('balance')
                        ->live()
                        ->state(function (Get $get, ?Rental $record): string {
                            $status = $get('status') ?? $record?->status;
                            $total = self::resolveTotalAmountInGrosze($get, $record);
                            $paid = (int) ($record?->paid_amount ?? 0);
                            $currency = $record?->currency ?? config('rental.currency', 'EUR');

                            return self::formatSettlementBalance($status, $total, $paid, $currency);
                        }),
                ])
                ->columns(3),
        ];
    }

    /**
     * Sekcja "Zasób" — placeholder z bieżącym rentable + Select do zmiany.
     *
     * Generyczna: używa config('rental.rentable_model'). Jeśli config pusty,
     * zwraca pustą tablicę (sekcja pomijana).
     *
     * @return array<int, \Filament\Schemas\Components\Component>
     *
     * @see KML-0047
     */
    public static function resourceSection(): array
    {
        $rentableModel = config('rental.rentable_model');
        if (! $rentableModel || ! class_exists($rentableModel)) {
            return [];
        }

        return [
            Section::make('Zasób')
                ->schema([

                    Select::make('rentable_id')
                        ->label('Villa')
                        ->options(fn () => self::rentableOptions())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcTotalAmount($get, $set))
                        ->rules([
                            fn (Get $get, ?Rental $record) => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                if (blank($value)) {
                                    return;
                                }
                                //dd($value);
                                $startAt = $get('start_at') ?? $get('start_date');
                                $endAt = $get('end_at') ?? $get('end_date');
                                if (blank($startAt) || blank($endAt)) {
                                    return;
                                }
                                $rentableModel = config('rental.rentable_model');
                                if (! $rentableModel) {
                                    return;
                                }
                                //dd($rentableModel);
                                $startStr = is_string($startAt) ? $startAt : $startAt->format('Y-m-d H:i:s');
                                $endStr = is_string($endAt) ? $endAt : $endAt->format('Y-m-d H:i:s');
                                $query = Rental::query()
                                    ->active()
                                    ->where('rentable_type', $rentableModel)
                                    ->where('rentable_id', $value)
                                    ->overlapping($startStr, $endStr);
                                if ($record?->getKey()) {
                                    $query->where('id', '!=', $record->getKey());
                                }
                                if ($query->exists()) {
                                    $fail('Wybrany motocykl ma już aktywną rezerwację w tym terminie.');
                                }
                            },
                        ])
                        ->helperText('Wybór nowego motocykla automatycznie przeliczy kwotę.'),
                ])
                ->columns(1),
        ];
    }

    /**
     * Buduje opcje Select rentable_id z modelu skonfigurowanego w
     * config('rental.rentable_model'). Filtruje available=true / published=true
     * jeśli kolumny istnieją w tabeli (sprawdzenie przez Schema).
     *
     * @return array<string, string>
     */
    public static function rentableOptions(): array
    {
        $rentableModel = config('rental.rentable_model');
        if (! $rentableModel || ! class_exists($rentableModel)) {
            return [];
        }

        /** @var \Illuminate\Database\Eloquent\Model $instance */
        $instance = new $rentableModel;
        $table = $instance->getTable();
        $query = $rentableModel::query();

        if (SchemaFacade::hasColumn($table, 'available')) {
            $query->where('available', true);
        }
        if (SchemaFacade::hasColumn($table, 'published')) {
            $query->where('published', true);
        }

        return $query->get()
            ->mapWithKeys(fn ($m) => [(string) $m->getKey() => (string) ($m->name ?? $m->title ?? class_basename($m->rentable_type).' #'.$m->getKey())])
            ->all();
    }

    /**
     * Przelicza total_amount na podstawie aktualnego stanu formularza.
     * Używa PricingStrategyInterface (wstrzykiwany przez container).
     * Wynik strategii jest w jednostkach głównych waluty (PLN) — zapisujemy
     * total_amount w groszach (* 100), zgodnie z modelem Rental.
     *
     * @see KML-0047
     */
    protected static function recalcTotalAmount(Get $get, Set $set): void
    {
        $rentableId = $get('rentable_id');
        // KML-0047: preferujemy start_at/end_at (datetime), fallback start_date/end_date.
        $startAt = $get('start_at') ?? $get('start_date');
        $endAt = $get('end_at') ?? $get('end_date');
        $qty = (int) ($get('qty') ?? 1);

        if (blank($rentableId) || blank($startAt) || blank($endAt)) {
            return;
        }
        $rentableModel = config('rental.rentable_model');
        if (! $rentableModel || ! class_exists($rentableModel)) {
            return;
        }
        /** @var \Illuminate\Database\Eloquent\Model|null $rentable */
        $rentable = $rentableModel::find($rentableId);
        if (! $rentable) {
            return;
        }

        $rentalType = null;
        if ($rentalTypeId = $get('rental_type_id')) {
            $rentalType = RentalType::find($rentalTypeId);
        }
        // datetime jesli mozliwe (Carbon ma format), inaczej Y-m-d
        $startStr = is_string($startAt)
            ? $startAt
            : (method_exists($startAt, 'format') ? $startAt->format('Y-m-d H:i:s') : (string) $startAt);
        $endStr = is_string($endAt)
            ? $endAt
            : (method_exists($endAt, 'format') ? $endAt->format('Y-m-d H:i:s') : (string) $endAt);

        //dd($rentalType, $rentableId, $startAt, $endAt, $qty);

        try {
            /** @var PricingStrategyInterface $strategy */
            $strategy = app(PricingStrategyInterface::class);
            // KML-0057: PerRentablePLNStrategy::calculate() zwraca grosze (int).
            // Form state oczekuje PLN (string z 2 miejscami po przecinku), wiec dzielimy /100.
            // Wczesniej brak konwersji powodowal x100 zawyzenie ("setki tysiecy" PLN w Aktualnej kwocie).

            $amountGrosze = $strategy->calculate($rentable, $rentalType, max(1, $qty), $startStr, $endStr);
            $amountPLN = $amountGrosze / 100;
            $set('total_amount', number_format($amountPLN, 2, '.', ''));

            Notification::make()
                ->title('Kwota przeliczona')
                ->body('Nowa kwota: '.number_format($amountPLN, 2, ',', ' ').' '.config('rental.currency', 'EUR'))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Nie udało się przeliczyć kwoty')
                ->body($e->getMessage())
                ->warning()
                ->send();
        }
    }

    /**
     * Konwertuje aktualny stan pola total_amount (PLN string z form-state)
     * na grosze (int). Fallback do $record->total_amount (DB w groszach).
     *
     * Form state dla total_amount jest w PLN (np. "199.00") — patrz
     * formatStateUsing / dehydrateStateUsing w form().
     *
     * @see KML-S9-02 (bug: kwota PLN zamiast groszy w edycji)
     */
    protected static function resolveTotalAmountInGrosze(Get $get, ?Rental $record): int
    {
        $raw = $get('total_amount');
        if ($raw !== null && $raw !== '') {
            return (int) round(((float) str_replace(',', '.', (string) $raw)) * 100);
        }

        return (int) ($record?->total_amount ?? 0);
    }

    /**
     * Formatuje komunikat dla pola settlement_balance ("Do rozliczenia w biurze").
     *
     * Logika (KML-0063):
     *  - status 'cancelled' / 'expired' → brak rozliczenia (status overrideuje balance);
     *  - balance = total - paid:
     *      * balance > 0 → klient doplaca roznice (offline w biurze),
     *      * balance < 0 → biuro zwraca klientowi nadplate,
     *      * balance == 0 + paid > 0 → oplacona w pelni,
     *      * balance == 0 + paid == 0 → bez rozliczenia (rezerwacja zerowa).
     *
     * UWAGA: status 'paid' NIE krotkocyrkluje balance — rzeczywista roznica
     * total vs paid decyduje (bug KML-0063: zmiana motocykla rosnie total
     * przy zachowanym status='paid' i niezmienionym paid_amount).
     *
     * @param  ?string  $status     Status rezerwacji (pending/confirmed/paid/cancelled/expired/null)
     * @param  int      $total      Calkowita kwota w groszach
     * @param  int      $paid       Zaplacona online kwota w groszach
     * @param  string   $currency   Symbol waluty (np. 'PLN')
     *
     * @see KML-0063 — fix logiki: status 'paid' nie nadpisuje balance
     */
    public static function formatSettlementBalance(?string $status, int $total, int $paid, string $currency): string
    {
        // 1) Anulowane / wygasle — status overrideuje balance.
        if ($status === 'cancelled' || $status === 'expired') {
            return 'Brak rozliczenia (status: '.($status === 'cancelled' ? 'Anulowana' : 'Wygasla').')';
        }

        $balance = $total - $paid;
        $formatted = number_format(abs($balance) / 100, 2, ',', ' ').' '.$currency;

        // 2) Klient ma doplacic w biurze.
        if ($balance > 0) {
            return 'Klient doplaca: '.$formatted;
        }

        // 3) Biuro zwraca klientowi nadplate.
        if ($balance < 0) {
            return 'Biuro zwraca klientowi: '.$formatted;
        }

        // 4) balance == 0 — rozroznienie "oplacona" vs "zerowa rezerwacja".
        if ($paid > 0) {
            return 'Oplacona w pelni (0,00 '.$currency.')';
        }

        return 'Bez rozliczenia (0,00 '.$currency.')';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->formatStateUsing(fn (string $state) => substr($state, 0, 8))
                    ->copyable()
                    ->copyableState(fn (string $state) => $state)
                    ->fontFamily('mono'),
                TextColumn::make('name')
                    ->label('Cliente')
                    ->description(fn (Rental $r) => $r->email)
                    ->searchable(['name', 'email', 'phone'])
                    ->sortable(),
                TextColumn::make('rentable')
                    ->label('Villa')
                    ->getStateUsing(function (Rental $r): string {
                        $rentable = $r->rentable;
                        if (! $rentable) {
                            return class_basename($r->rentable_type).' #'.$r->rentable_id;
                        }

                        return $rentable->name ?? $rentable->title ?? class_basename($r->rentable_type).' #'.$rentable->getKey();
                    }),
                TextColumn::make('start_date')
                    ->label('Desde')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Hasta')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('qty')
                    ->label('Cant.')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_amount')
                    ->label('Totas')
                    ->formatStateUsing(fn (int $state, Rental $r) => number_format($state / 100, 2, ',', ' ').' '.$r->currency)
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'paid' => 'success',
                        'cancelled' => 'danger',
                        'expired' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pending' => 'Pendiente',
                        'confirmed' => 'Confirmada',
                        'paid' => 'Pagada',
                        'cancelled' => 'Anulada',
                        'expired' => 'Expirada',
                        default => $state,
                    }),
                TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pendiente',
                        'confirmed' => 'Confirmada',
                        'paid' => 'Pagada',
                        'cancelled' => 'Anulada',
                        'expired' => 'Expirada',
                    ])
                    ->multiple(),
                Filter::make('start_date_range')
                    ->label('Fechas')
                    ->schema([
                        DatePicker::make('from')->label('Desde')->native(false),
                        DatePicker::make('to')->label('Hasta')->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $d) => $q->where('end_date', '>=', $d))
                            ->when($data['to'] ?? null, fn ($q, $d) => $q->where('start_date', '<=', $d));
                    }),
            ])
            ->recordActions([
                Action::make('markPaid')
                    ->label('Pagado')
                    ->icon(Heroicon::OutlinedCurrencyDollar)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Rental $r) => in_array($r->status, ['pending', 'confirmed'], true))
                    ->action(function (Rental $r) {
                        app(RentalService::class)->markPaid($r);
                        Notification::make()->title('Reserva marcada como pagada')->success()->send();
                    }),
                Action::make('confirm')
                    ->label('Confirm')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (Rental $r) => $r->status === 'pending')
                    ->action(function (Rental $r) {
                        app(RentalService::class)->confirm($r);
                        Notification::make()->title('Reserva confirmada')->success()->send();
                    }),
                Action::make('cancel')
                    ->label('Anular')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Rental $r) => ! in_array($r->status, ['cancelled', 'expired'], true))
                    ->action(function (Rental $r) {
                        app(RentalService::class)->cancel($r);
                        Notification::make()->title('Reserva anulada')->warning()->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVillas::route('/'),
            'create' => Pages\CreateVillas::route('/create'),
            'edit' => Pages\EditVillas::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['rentable']);
    }
}
