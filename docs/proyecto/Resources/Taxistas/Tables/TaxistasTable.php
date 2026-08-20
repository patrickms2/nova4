<?php

namespace App\Filament\App\Resources\Taxistas\Tables;

use App\Filament\App\Resources\TaxistaTaxis\Tables\TaxistaTaxisTable;
use App\Filament\App\Resources\Taxistas\TaxistaResource;
use App\Models\BookingDepartment;
use App\Models\Taxista;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Average;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Guava\FilamentIconSelectColumn\Tables\Columns\IconSelectColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Components\Tables\InlineEditColumn;
use Illuminate\Support\Collection;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Support\Enums\Width;
use Filament\Schemas\Components\Section;


class TaxistasTable
{
    public static function extractLicenseNumber(?string $license): ?int
    {
        if (! filled($license)) {
            return null;
        }

        if (preg_match('/LM\s*(\d+)/iu', (string) $license, $matches) === 1) {
            return (int) $matches[1];
        }

        if (preg_match('/(\d+)/', (string) $license, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    public static function formatLicenseForDisplay(?string $license): string
    {
        if (! filled($license)) {
            return '—';
        }

        $trimmedLicense = trim((string) $license);
        $licenseNumber = self::extractLicenseNumber($trimmedLicense);

        if ($licenseNumber === null) {
            return $trimmedLicense;
        }

        if (preg_match('/^LM\s*\d+\s+([A-ZÁÉÍÓÚÜÑ]+)$/iu', $trimmedLicense, $matches) === 1) {
            return sprintf('LM %02d %s', $licenseNumber, strtoupper($matches[1]));
        }

        return (string) $licenseNumber;
    }

    public static function resolveAggregatedTaxiOnlineStateForTaxista(Taxista $record): string
    {
        $taxis = $record->taxis;

        if (! $taxis instanceof Collection || $taxis->isEmpty()) {
            return 'sin-taxi';
        }

        $hasTrackingCode = false;

        foreach ($taxis as $taxi) {
            $state = TaxistaTaxisTable::resolveTaxiOnlineStateForRecord($taxi);

            if ($state === 'online') {
                return 'online';
            }

            if ($state !== 'sin-codigo') {
                $hasTrackingCode = true;
            }
        }

        return $hasTrackingCode ? 'offline' : 'sin-codigo';
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query): Builder => $query
                ->with([
                    'type:id,label',
                    'municipio:id,nombre',
                    'taxis:id,taxista_user_id,tracking_uuid,last_located_at',
                ])
                ->withCount([
                    'appointments',
                    'documents',
                    'tickets',
                    'taxis',
                    'conductores',
                ])
                ->where('status', true)
                ->whereIn('role', ['taxista', 'admin', 'super'])
            )
            ->defaultSort(
                fn(Builder $query): Builder => $query
                    ->orderBy('is_featured', 'desc')
                    ->orderBy('municipio_id')
                    ->orderByRaw("CASE WHEN REGEXP_SUBSTR(COALESCE(licencia, ''), '[0-9]+') IS NULL THEN 1 ELSE 0 END")
                    ->orderByRaw("CAST(COALESCE(REGEXP_SUBSTR(COALESCE(licencia, ''), '[0-9]+'), '0') AS UNSIGNED)")
                    ->orderBy('name')
            )
            ->columns([
                           TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),


                IconSelectColumn::make('status')
                    ->label('Estado')
                    ->sortable()
                    ->icons([
                            true => 'heroicon-o-check-circle',
                            false => 'heroicon-o-x-circle',
                        ]
                    )
                    ->colors([
                        true => 'success',
                        false => 'gray',
                    ])
                    ->default(false)
                                        ->toggleable(isToggledHiddenByDefault: false),

                IconSelectColumn::make('is_featured')
                    ->label('Destacado')
                    ->icons([
                            true => 'heroicon-s-star',
                            false => 'heroicon-o-x-circle',
                        ]
                    )
                    ->colors([
                        true => 'warning',
                        false => 'gray',
                    ])
                    ->default(false)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('is_online')
                    ->label('Online')
                    ->badge()
                    ->formatStateUsing(fn(bool $state): string => $state ? 'Online' : 'Offline')
                    ->color(fn(bool $state): string => $state ? 'success' : 'gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('taxi_online')
                    ->label('Taxi online')
                    ->badge()
                    ->state(fn (Taxista $record): string => self::resolveAggregatedTaxiOnlineStateForTaxista($record))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'online' => 'Online',
                        'offline' => 'Offline',
                        'sin-codigo' => 'Sin código',
                        default => 'Sin taxi',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'online' => 'success',
                        'offline' => 'warning',
                        'sin-codigo' => 'gray',
                        default => 'gray',
                    })
                     ->toggleable(isToggledHiddenByDefault: false),


                TextColumn::make('name')
                    ->label('Nombre')
                    ->formatStateUsing(fn(mixed $state, Taxista $record): string => trim(($record->name_last ?? '') . ' ' . ($record->name_first ?? '')) ?: (string)($state ?? '-'))
                    ->searchable(['name', 'name_first', 'name_last', 'nif', 'phone', 'email', 'licencia'])
                    ->sortable(['name_last', 'name_first']),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nif')
                    ->label('NIF')
                    ->searchable()
                    ->sortable()
                ,

                TextColumn::make('municipio.nombre')
                    ->label('Municipio')
                    ->sortable()
                    ->searchable()                   
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('phone')
                    ->label('Telefono')
                    ->searchable()
                    ->url(fn(Taxista $record): ?string => filled($record->phone) ? 'tel:' . preg_replace('/\s+/', '', (string)$record->phone) : null)
                    ->toggleable(isToggledHiddenByDefault: true),

                InlineEditColumn::make('licencia')
                    ->label('Licencia')
                    ->formatStateUsing(fn (?string $state): string => self::formatLicenseForDisplay($state))
                    ->toggleable()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->orderByRaw("CASE WHEN REGEXP_SUBSTR(COALESCE(licencia, ''), '[0-9]+') IS NULL THEN 1 ELSE 0 END")
                            ->orderByRaw("CAST(COALESCE(REGEXP_SUBSTR(COALESCE(licencia, ''), '[0-9]+'), '0') AS UNSIGNED) {$direction}")
                            ->orderBy('licencia', $direction);
                    })
                    ->searchable()
                   ,
                TextColumn::make('type.label')
                    ->label('Tipo')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('appointments_count')
                    ->label('Citas')
                    ->badge()
                    ->color('info')
                    ->summarize([
                        Sum::make()->label('Total citas'),
                    ])
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('documents_count')
                    ->label('Docs')
                    ->badge()
                    ->summarize([
                        Sum::make()->label('Total docs'),
                    ])
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('tickets_count')
                    ->label('Tickets')
                    ->badge()
                    ->color('warning')
                    ->summarize([
                        Sum::make()->label('Total tickets'),
                    ])
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('taxis_count')
                    ->label('Taxis')
                    ->badge()
                    ->color('success')
                    ->summarize([
                        Sum::make()->label('Total taxis'),
                    ])
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('conductores_count')
                    ->label('Conductores')
                    ->badge()
                    ->color('primary')
                    ->summarize([
                        Sum::make()->label('Total conductores'),
                    ])
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('updated_at')
                    ->label('Ultima actividad')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('last_location_at')
                    ->label('Ubicacion')
                    ->since()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type_id')
                    ->label('Tipo')
                    ->relationship('type', 'label')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('status')
                    ->label('Activo')
                    ->trueLabel('Activos')
                    ->falseLabel('Baja')
                    ->placeholder('Todos'),
                TernaryFilter::make('is_featured')
                    ->label('Destacado')
                    ->trueLabel('Destacados')
                    ->falseLabel('No destacados')
                    ->placeholder('Todos'),
                TernaryFilter::make('has_taxista')
                    ->label('Taxista asociado')
                    ->trueLabel('Con Taxista')
                    ->falseLabel('Sin Taxista')
                    ->placeholder('Todos')
                    ->queries(
                        true: fn (Builder $query): Builder => $query
                            ->whereNotNull('taxista_id')
                            ->where('taxista_id', '!=', ''),
                        false: fn (Builder $query): Builder => $query
                            ->where(function (Builder $nestedQuery): Builder {
                                return $nestedQuery
                                    ->whereNull('taxista_id')
                                    ->orWhere('taxista_id', '');
                            }),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                TernaryFilter::make('has_licencia')
                    ->label('Licencia')
                    ->trueLabel('Con licencia')
                    ->falseLabel('Sin licencia')
                    ->placeholder('Todos')
                    ->queries(
                        true: fn (Builder $query): Builder => $query
                            ->whereNotNull('licencia')
                            ->where('licencia', '!=', ''),
                        false: fn (Builder $query): Builder => $query
                            ->where(function (Builder $nestedQuery): Builder {
                                return $nestedQuery
                                    ->whereNull('licencia')
                                    ->orWhere('licencia', '');
                            }),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                SelectFilter::make('municipio_id')
                    ->label('Municipio')
                    ->relationship('municipio', 'nombre'),

                Filter::make('has_documents')
                    ->label('Tiene documentos')
                    ->query(fn(Builder $query): Builder => $query->whereHas('documents')),
              
                    Filter::make('licencia')
                    ->label('Licencia')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('value')
                            ->label('Buscar en licencia')
                            ->placeholder('Ej: 123, A-45...'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = trim((string)($data['value'] ?? ''));

                        if ($value === '') {
                            return $query;
                        }

                        return $query->whereHas('legacyUsuario', fn(Builder $legacyQuery): Builder => $legacyQuery->where('licencia', 'like', "%{$value}%"));
                    }),
            ])
               ->filtersLayout(FiltersLayout::Modal)
                ->filtersFormSchema(fn(array $filters): array => [
                    Section::make()
                        ->schema([
                            $filters['type_id'],
                            $filters['status'],
                            $filters['is_featured'],
                            $filters['has_taxista'],
                            $filters['has_licencia'],
                            $filters['municipio_id'],
                            $filters['has_documents'],
                            $filters['licencia'],
                        ])
                        ->columns(3)
                        ->columnSpanFull(),
                ])
                ->filtersFormWidth(Width::ThreeExtraLarge)
            ->recordActions([
                 ViewAction::make(),
                EditAction::make(),

                Action::make('ayuda')
                    ->label('Ayuda')
                    ->icon('heroicon-o-question-mark-circle')
                    ->color('gray')
                    ->tooltip('Ver secciones disponibles')
                    ->url(fn(Taxista $record): string => TaxistaResource::getUrl('view', ['record' => $record])),

                Action::make('mapa')
                    ->label('Mapa')
                    ->icon('heroicon-o-map')
                    ->color('gray')
                    ->url(fn(Taxista $record): ?string => filled($record->last_lat) && filled($record->last_lng)
                        ? sprintf('https://www.google.com/maps?q=%s,%s', $record->last_lat, $record->last_lng)
                        : null)
                    ->openUrlInNewTab()
                    ->visible(fn(Taxista $record): bool => filled($record->last_lat) && filled($record->last_lng)),

                Action::make('documentos')
                    ->label('Documentos')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->url(fn(Taxista $record): string => TaxistaResource::getUrl('documentos', ['record' => $record]))
                    ->visible(fn(): bool => method_exists(TaxistaResource::class, 'getUrl')),

                Action::make('citas')
                    ->label('Citas')
                    ->icon('heroicon-o-calendar-days')
                    ->color('gray')
                    ->url(fn(Taxista $record): string => TaxistaResource::getUrl('citas', ['record' => $record]))
                    ->visible(fn(): bool => method_exists(TaxistaResource::class, 'getUrl')),

                Action::make('conductores')
                    ->label('Conductores')
                    ->icon('heroicon-o-users')
                    ->color('gray')
                    ->url(fn(Taxista $record): string => TaxistaResource::getUrl('conductores', ['record' => $record]))
                    ->visible(fn(): bool => method_exists(TaxistaResource::class, 'getUrl')),

                Action::make('toggle_status')
                    ->label(fn(Taxista $record): string => $record->status ? 'Bloquear' : 'Activar')
                    ->icon(fn(Taxista $record): string => $record->status ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(fn(Taxista $record): string => $record->status ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(fn(Taxista $record): bool => $record->update(['status' => !$record->status])),
            
                
               
            ])
            ->recordAction('view')
   
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('Cambiar MUNICIPIO')
                        ->icon('heroicon-m-pencil-square')
                        ->form([
                            Select::make('municipio_id')
                                ->label('Municipios')
                                ->default(1)
                                ->relationship('municipio', 'nombre')
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each->update(['municipio_id' => $data['municipio_id']]);
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('Departamento')
                        ->icon('heroicon-m-pencil-square')
                        ->form([
                            Select::make('departamentos')
                                ->label('Departamentos')
                                ->options(BookingDepartment::all()->pluck('nombre', 'id'))
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each->update(['departamentos' => $data['departamentos']]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('featureSelected')
                        ->label('Feature Selected')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->action(function ($records): void {
                            foreach ($records as $record) {
                                $record->update(['is_featured' => true]);
                            }

                            Notification::make()
                                ->title('Selected posts featured successfully')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->visible(),
                    BulkAction::make('activate')
                        ->label('Activar')
                        ->icon('heroicon-m-check-circle')
                        ->requiresConfirmation()
                        ->action(fn(User $records) => $records->each->update(['status' => 1])),
                    BulkAction::make('deactivate')
                        ->label('Desactivar')
                        ->icon('heroicon-m-x-circle')
                        ->requiresConfirmation()
                        ->action(fn(User $records) => $records->each->update(['status' => 0])),
                    DeleteBulkAction::make(),
                ])
 ,
            ])      
            ->paginated([10, 25, 50, 100, 'all']);
    }
}
