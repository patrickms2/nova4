<?php

namespace App\Filament\App\Resources\TaxistaTaxis\Tables;

use App\Filament\Portal\Pages\TaxistaTracking;
use App\Support\TrackingConnectivity;
use App\Services\TraccarService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput as FormTextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema as DbSchema;
use Illuminate\Support\Str;

class TaxistaTaxisTable
{
    public static function onlineThresholdMinutes(): int
    {
        return TrackingConnectivity::onlineThresholdMinutes();
    }

    public static function resolveTaxistaOnlineStateForRecord(object $record): string
    {
        $taxista = $record->taxista ?? null;

        if (! $taxista) {
            return 'sin-taxista';
        }

        return (bool) ($taxista->is_online ?? false) ? 'online' : 'offline';
    }

    public static function resolveTrackingStateForRecord(object $record, ?bool $hasTrackingUuidColumn = null): string
    {
        $attributes = method_exists($record, 'getAttributes') ? $record->getAttributes() : [];
        $hasTrackingUuid = $hasTrackingUuidColumn
            ?? DbSchema::hasColumn('taxista_taxis', 'tracking_uuid')
            || array_key_exists('tracking_uuid', $attributes);

        if (! $hasTrackingUuid) {
            return 'sin-codigo';
        }

        if (! array_key_exists('tracking_uuid', $attributes) || ! filled($attributes['tracking_uuid'])) {
            return 'sin-codigo';
        }

        if (! filled($record->last_located_at ?? null)) {
            return 'sin-ping';
        }

        return $record->last_located_at->diffInMinutes(now()) <= self::onlineThresholdMinutes()
            ? 'activo'
            : 'inactivo';
    }

    public static function resolveTaxiOnlineStateForRecord(object $record, ?bool $hasTrackingUuidColumn = null): string
    {
        return match (self::resolveTrackingStateForRecord($record, $hasTrackingUuidColumn)) {
            'activo' => 'online',
            'inactivo', 'sin-ping' => 'offline',
            default => 'sin-codigo',
        };
    }

    private static function resolveMapUrlForRecord(object $record): string
    {
        try {
            return TaxistaTracking::getUrl([
                'taxi' => (int) ($record->id ?? 0),
            ], panel: 'portal');
        } catch (\Throwable) {
            return '#';
        }
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('taxista:id,name,is_online'))
            ->columns([
                Stack::make([
                    Stack::make([
                        TextColumn::make('license_plate')
                            ->label('Matricula')
                            ->icon('heroicon-m-truck')
                            ->iconColor('info')
                            ->weight(FontWeight::Bold)
                            ->searchable()
                            ->sortable(),

                        TextColumn::make('taxista.name')
                            ->label('Taxista')
                            ->icon('heroicon-m-user')
                            ->iconColor('warning')
                            ->weight(FontWeight::SemiBold)
                            ->searchable()
                            ->placeholder('Sin asignar')
                            ->extraAttributes(['class' => 'border-t dark:border-t-gray-200/20 pt-1']),
                    ])->space(1),

                    Stack::make([
                        TextColumn::make('vehicle_brand')
                            ->label('Marca')
                            ->icon('heroicon-m-tag')
                            ->color('gray')
                            ->placeholder('Sin marca'),

                        TextColumn::make('vehicle_model')
                            ->label('Modelo')
                            ->icon('heroicon-m-tag')
                            ->color('gray')
                            ->placeholder('Sin modelo'),

                        TextColumn::make('municipality')
                            ->label('Municipio')
                            ->icon('heroicon-m-map-pin')
                            ->color('gray')
                            ->placeholder('Sin municipio'),
                    ]),

                    Stack::make([
                        TextColumn::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'activo' => 'success',
                                'mantenimiento' => 'warning',
                                'baja' => 'danger',
                                default => 'gray',
                            }),
                        TextColumn::make('tracking_mode')
                            ->label('Tracking')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'real' => 'success',
                                'simulated' => 'warning',
                                'disabled' => 'gray',
                                default => 'gray',
                            }),
                        TextColumn::make('taxista_online')
                            ->label('Taxista')
                            ->badge()
                            ->state(fn ($record): string => self::resolveTaxistaOnlineStateForRecord($record))
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'online' => 'Online',
                                'offline' => 'Offline',
                                default => 'Sin taxista',
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'online' => 'success',
                                'offline' => 'gray',
                                default => 'warning',
                            }),
                        TextColumn::make('taxi_online')
                            ->label('Taxi')
                            ->badge()
                            ->state(fn ($record): string => self::resolveTaxiOnlineStateForRecord($record))
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'online' => 'Online',
                                'offline' => 'Offline',
                                default => 'Sin código',
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'online' => 'success',
                                'offline' => 'warning',
                                default => 'gray',
                            }),
                        TextColumn::make('is_accessible')
                            ->label('Accesibilidad')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'PMR' : 'Estandar')
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                    ])->extraAttributes(['class' => 'flex flex-row gap-2 border-b dark:border-b-gray-200/20 pb-2']),

                    Stack::make([
                        TextColumn::make('tracking_code')
                            ->label('Codigo tracking')
                            ->icon('heroicon-m-finger-print')
                            ->visible(fn (): bool => DbSchema::hasColumn('taxista_taxis', 'tracking_uuid'))
                            ->getStateUsing(function ($record): ?string {
                                if (! DbSchema::hasColumn('taxista_taxis', 'tracking_uuid')) {
                                    return null;
                                }

                                $attributes = $record->getAttributes();

                                return array_key_exists('tracking_uuid', $attributes)
                                    ? (string) ($attributes['tracking_uuid'] ?? '')
                                    : null;
                            })
                            ->copyable()
                            ->copyMessage('Codigo tracking copiado')
                            ->copyMessageDuration(1200)
                            ->placeholder('Sin codigo'),

                        TextColumn::make('seats')
                            ->label('Plazas')
                            ->icon('heroicon-m-user-group')
                            ->formatStateUsing(fn (?int $state): string => $state ? "{$state}" : '-'),

                        TextColumn::make('last_located_at')
                            ->label('Ultima localizacion')
                            ->dateTime('d/m/Y H:i')
                            ->icon('heroicon-m-clock')
                            ->iconColor('info')
                            ->placeholder('Sin ubicacion'),

                        TextColumn::make('tracking_state')
                            ->label('Tracking')
                            ->badge()
                            ->getStateUsing(fn ($record): string => self::resolveTrackingStateForRecord($record))
                            ->color(fn (string $state): string => match ($state) {
                                'activo' => 'success',
                                'inactivo' => 'warning',
                                default => 'gray',
                            }),
                    ]),
                ])->extraAttributes(['class' => 'flex gap-3']),
            ])
            ->groups([
                Group::make('status')
                    ->label('Estado'),
                Group::make('taxista.name')
                    ->label('Taxista'),
                Group::make('municipality')
                    ->label('Municipio'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'activo' => 'Activo',
                        'mantenimiento' => 'Mantenimiento',
                        'baja' => 'Baja',
                    ]),
                SelectFilter::make('tracking_connectivity')
                    ->label('Taxi online')
                    ->options([
                        'online' => 'Online',
                        'offline' => 'Offline',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! DbSchema::hasColumn('taxista_taxis', 'last_located_at')) {
                            return $query;
                        }

                        $value = $data['value'] ?? null;

                        if ($value === 'online') {
                            return $query
                                ->whereNotNull('last_located_at')
                                ->where('last_located_at', '>=', now()->subMinutes(self::onlineThresholdMinutes()));
                        }

                        if ($value === 'offline') {
                            return $query->where(function (Builder $subQuery): void {
                                $subQuery
                                    ->whereNull('last_located_at')
                                    ->orWhere('last_located_at', '<', now()->subMinutes(self::onlineThresholdMinutes()));
                            });
                        }

                        return $query;
                    }),
            ])
            ->defaultSort('updated_at', 'desc')
            ->contentGrid([
                'sm' => 1,
                'md' => 2,
                '2xl' => 3,
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view_on_map')
                        ->label('Ver en mapa')
                        ->icon('heroicon-m-map')
                        ->url(fn ($record): string => self::resolveMapUrlForRecord($record))
                        ->openUrlInNewTab()
                        ->color('info'),
                    Action::make('validate_tracking')
                        ->label('Validar')
                        ->icon('heroicon-m-shield-check')
                        ->visible(fn (): bool => DbSchema::hasColumn('taxista_taxis', 'tracking_uuid'))
                        ->action(function ($record): void {
                            $trackingUuid = trim((string) ($record->tracking_uuid ?? ''));

                            if ($trackingUuid === '') {
                                Notification::make()
                                    ->title('Taxi sin código tracking')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $traccarService = app(TraccarService::class);
                            $authenticated = $traccarService->ensureAuthenticated();

                            if (! $authenticated) {
                                Notification::make()
                                    ->title('No se pudo conectar con Traccar')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $device = $traccarService->findTraccarDeviceByUniqueId($trackingUuid);

                            Notification::make()
                                ->title($device ? 'Código validado en Traccar' : 'Código no encontrado en Traccar')
                                ->color($device ? 'success' : 'warning')
                                ->send();
                        }),
                    Action::make('add_tracking_code')
                        ->label('Añadir código')
                        ->icon('heroicon-m-finger-print')
                        ->visible(fn (): bool => DbSchema::hasColumn('taxista_taxis', 'tracking_uuid'))
                        ->fillForm(fn ($record): array => [
                            'tracking_uuid' => filled($record->tracking_uuid) ? (string) $record->tracking_uuid : (string) Str::ulid(),
                        ])
                        ->form([
                            FormTextInput::make('tracking_uuid')
                                ->label('Código tracking')
                                ->required()
                                ->maxLength(64),
                        ])
                        ->action(function (array $data, $record): void {
                            $trackingUuid = trim((string) ($data['tracking_uuid'] ?? ''));

                            if ($trackingUuid === '') {
                                return;
                            }

                            $record->update([
                                'tracking_uuid' => $trackingUuid,
                            ]);

                            Notification::make()
                                ->title('Código tracking actualizado')
                                ->success()
                                ->send();
                        }),
                    EditAction::make(),
                    DeleteAction::make(),
                ])->color('gray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
