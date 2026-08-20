<?php

namespace App\Filament\App\Resources\Taxistas\Pages;

use App\Filament\App\Resources\Taxis\Schemas\TaxiForm;
use App\Filament\App\Resources\Taxistas\TaxistaResource;
use App\Filament\App\Resources\TaxistaTaxis\Schemas\TaxistaTaxiForm;
use App\Services\TraccarService;
use App\Models\TaxistaTaxi;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Guava\FilamentIconSelectColumn\Tables\Columns\IconSelectColumn;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Schema as DbSchema;
use Illuminate\Support\Str;
use Livewire\Livewire;

class ManageTaxisTaxista extends ManageRelatedRecords
{
    protected static string $resource = TaxistaResource::class;

    protected static string $relationship = 'taxis';

    protected static ?string $navigationLabel = 'Taxis';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();

        return (string)$record->taxis()->count();
    }

    public function getHeading(): string|Htmlable|null
    {
        return (string)($this->getRecord()->name ?? 'Taxista');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Flota asociada al taxista.';
    }

    public function form(Schema $schema): Schema
    {
        return TaxistaTaxiForm::configure($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('verify_tracking_codes')
                ->label('Verificar Códigos Tracking')
                ->icon('heroicon-o-shield-check')
                ->color('gray')
                ->action(function (): void {
                    if (! DbSchema::hasColumn('taxista_taxis', 'tracking_uuid')) {
                        Notification::make()
                            ->title('Tracking no disponible en este esquema')
                            ->warning()
                            ->send();

                        return;
                    }

                    $ownerId = (int) $this->getRecord()->id;

                    $taxis = TaxistaTaxi::query()
                        ->where('taxista_user_id', $ownerId)
                        ->get(['id', 'license_plate', 'tracking_uuid']);

                    if ($taxis->isEmpty()) {
                        Notification::make()
                            ->title('Este taxista no tiene taxis asignados')
                            ->warning()
                            ->send();

                        return;
                    }

                    $validIdentifiers = $taxis
                        ->pluck('tracking_uuid')
                        ->filter(fn ($value): bool => filled($value))
                        ->map(fn ($value): string => trim((string) $value))
                        ->filter(fn (string $value): bool => Str::isUuid($value) || Str::isUlid($value))
                        ->values();

                    $invalidCount = (int) $taxis->count() - (int) $validIdentifiers->count();

                    $verifiedInTraccarCount = 0;
                    $traccarAuthenticated = app(TraccarService::class)->ensureAuthenticated();

                    if ($traccarAuthenticated) {
                        $verifiedInTraccarCount = $validIdentifiers
                            ->filter(function (string $trackingUuid): bool {
                                return (bool) app(TraccarService::class)->findTraccarDeviceByUniqueId($trackingUuid);
                            })
                            ->count();
                    }

                    Notification::make()
                        ->title('Verificación de tracking completada')
                        ->body(sprintf(
                            'Taxis: %d · Códigos válidos: %d · Inválidos/vacíos: %d · Validados en Traccar: %d%s',
                            (int) $taxis->count(),
                            (int) $validIdentifiers->count(),
                            $invalidCount,
                            $verifiedInTraccarCount,
                            $traccarAuthenticated ? '' : ' · Traccar no autenticado'
                        ))
                        ->color($invalidCount === 0 ? 'success' : 'warning')
                        ->send();
                }),

            Action::make('attach_existing_taxi')
                ->label('Adjuntar taxi existente')
                ->icon('heroicon-o-paper-clip')
                ->form([
                    Select::make('taxi_id')
                        ->label('Taxi')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(fn() => TaxistaTaxi::query()
                            ->where(function ($query): void {
                                $query->whereNull('taxista_user_id')
                                    ->orWhere('taxista_user_id', 0);
                            })
                            ->orderByDesc('id')
                            ->get()
                            ->mapWithKeys(fn(TaxistaTaxi $taxi): array => [
                                $taxi->id => trim(($taxi->license_plate ?? '-') . ' | ' . ($taxi->licencia ?? '-')),
                            ])
                            ->all()),
                ])
                ->action(function (array $data): void {
                    $owner = $this->getRecord();
                    $taxi = TaxistaTaxi::query()
                        ->whereKey($data['taxi_id'])
                        ->where(function ($query): void {
                            $query->whereNull('taxista_user_id')
                                ->orWhere('taxista_user_id', 0);
                        })
                        ->first();

                    if (!$taxi) {
                        Notification::make()
                            ->title('El taxi ya no está disponible para adjuntar')
                            ->danger()
                            ->send();

                        return;
                    }

                    $taxi->update([
                        'taxista_user_id' => (int) $owner->id,
                        'status' => (string) ($taxi->status ?? 'activo') === 'pending_assignment' ? 'activo' : $taxi->status,
                    ]);

                    Notification::make()
                        ->title('Taxi adjuntado correctamente')
                        ->success()
                        ->send();
                }),
            CreateAction::make()
                ->label('Añadir taxi')
                ->fillForm(function (): array {
                    $owner = $this->getRecord();

                    return [
                        'taxista_user_id' => $owner->id,
                    ];
                })
                ->mutateFormDataUsing(function (array $data): array {
                    $owner = $this->getRecord();
                    $data['taxista_user_id'] = $owner->id;

                    return $data;
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('matricula')
                    ->label('Matrícula')
                    ->getStateUsing(fn($record) => $record->license_plate ?? $record->matricula ?? $record->plate ?? '-')
                    ->description(fn($record) => $record->vehicle_model ?? $record->modelo ?? $record->model ?? 'Sin modelo')
                    ->badge()
                    ->color('info')
                    ->searchable(),
                TextColumn::make('licencia.name')
                    ->label('Licencia')
                    ->getStateUsing(fn($record) => $record->licencia->name ?? '-')
                    ->description(fn($record) => $record->municipios->nombre ?? '-')
                    ->badge()
                    ->searchable()
                    ->toggleable(),

                IconSelectColumn::make('dangerous')
                    ->label('Estado')
                    ->options([
                        'activo' => 'Activo',
                        'pending_assignment' => 'Pendiente asignación',
                        'mantenimiento' => 'Mantenimiento',
                        'baja' => 'Baja',
                    ])
                    ->getStateUsing(fn($record) => $record->status ?? 'activo'),

                TextColumn::make('notes')
                    ->label('Conductor')
                    ->placeholder('-')
                    ->wrap()
                    ->searchable(),

            ])
            ->headerActions([])
            ->recordActions([
                Action::make('assign_conductor')
                    ->label('Conductor')
                    ->icon('heroicon-o-user-plus')
                    ->color('gray')
                    ->fillForm(fn($record): array => [
                        'conductor_id' => $this->resolveSelectedConductorId($record),
                    ])
                    ->form([
                        Select::make('conductor_id')
                            ->label('Conductor')
                            ->searchable()
                            ->preload()
                            ->placeholder('Sin asignar')
                            ->options(fn(): array => $this->conductorOptionsForOwner()),
                    ])
                    ->action(function (array $data, $record): void {
                        $conductorId = (int)($data['conductor_id'] ?? 0);

                        if ($conductorId <= 0) {
                            $record->update(['notes' => null]);

                            return;
                        }

                        $conductor = User::query()
                            ->select(['id', 'name', 'taxista_id', 'role'])
                            ->whereKey($conductorId)
                            ->where('role', 'conductor')
                            ->where('taxista_id', (int)$this->getRecord()->id)
                            ->first();

                        if (!$conductor) {
                            Notification::make()
                                ->title('El conductor seleccionado no está asociado al taxista')
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->update([
                            'notes' => $conductor->name,
                        ]);

                        Notification::make()
                            ->title('Conductor asignado al taxi')
                            ->success()
                            ->send();
                    }),
                EditAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $owner = $this->getRecord();
                        $data['taxista_user_id'] = $owner->id;

                        return $data;
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([])
            ->defaultSort('id', 'desc');
    }

    /**
     * @return array<int, string>
     */
    private function conductorOptionsForOwner(): array
    {
        return User::query()
            ->where('role', 'conductor')
            ->where('taxista_id', (int)$this->getRecord()->id)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    private function resolveSelectedConductorId(object $record): ?int
    {
        $conductorName = trim((string)($record->notes ?? ''));

        if ($conductorName === '') {
            return null;
        }

        $conductorId = User::query()
            ->where('role', 'conductor')
            ->where('taxista_id', (int)$this->getRecord()->id)
            ->where('name', $conductorName)
            ->value('id');

        return $conductorId ? (int)$conductorId : null;
    }
}
