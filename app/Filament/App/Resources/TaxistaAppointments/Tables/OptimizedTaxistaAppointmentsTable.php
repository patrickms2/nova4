<?php

namespace App\Filament\App\Resources\TaxistaAppointments\Tables;

use App\Models\BookingDepartment;
use App\Models\Taxista;
use App\Models\Taxi\TipoCitas;
use App\Enums\CitaStatus;
use App\Support\PortalTaxistaContext;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Guava\FilamentIconSelectColumn\Tables\Columns\IconSelectColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class OptimizedTaxistaAppointmentsTable
{
    public static function configure(Table $table): Table
    {
        if (!PortalTaxistaContext::isPortalPanel()) {
            return $table
                // QUERY OPTIMIZADA: selects específicos y corregido orWhere
                ->modifyQueryUsing(fn(Builder $query): Builder => $query
                    ->with([
                        'taxista:id,name',
                        'booking_department:id,name,color', 
                        'tipo:id,nombre'
                    ])
                    ->where(function (Builder $q): void {
                        $q->where('starts_at', '>', now())
                         ->orWhere('status', 'pendiente');
                    })
                    ->orderBy('starts_at', 'desc')
                )
                // GROUPINGS OPTIMIZADOS: solo los necesarios
                ->groups([
                    TableGroup::make('taxista.name')
                        ->label('Usuario')
                        ->titlePrefixedWithLabel(true)
                        ->collapsible(true),
                    TableGroup::make('booking_department.name')
                        ->label('Departamento')
                        ->titlePrefixedWithLabel(true)
                        ->collapsible(true),
                ])
                ->columns([
                    TextColumn::make('starts_at')
                        ->label('Fecha')
                        ->color('black')
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->state(fn($record): string => self::renderMesDiaHora(
                            $record->starts_at->format('M'),
                            $record->starts_at->format('d')
                        ))
                        ->html(),
                        
                    TextColumn::make('hora')
                        ->label('Hora')
                        ->dateTime('H:i')
                        ->color('danger')
                        ->size('xl')
                        ->weight(FontWeight::Bold)
                        ->extraAttributes(['style' => 'font-size: 16px; font-weight: bold'])
                        ->badge()
                        ->state(fn($record): string => $record->starts_at->format('H:i'))
                        ->toggleable(isToggledHiddenByDefault: false),

                    TextColumn::make('taxista.name')
                        ->label('Taxista')
                        ->searchable()
                        ->sortable()
                        ->visible(fn(): bool => !PortalTaxistaContext::isPortalPanel()),

                    TextColumn::make('title')
                        ->label('Asunto')
                        ->searchable()
                        ->sortable()
                        ->limit(50),

                    TextColumn::make('booking_department.name')
                        ->label('Departamento')
                        ->badge()
                        ->color(fn($record): string => $record->booking_department?->color ?? 'gray')
                        ->placeholder('Sin departamento'),

                    IconSelectColumn::make('status')
                        ->label('Estado')
                        ->options(CitaStatus::class),

                    TextColumn::make('tipo.nombre')
                        ->label('Tipo')
                        ->badge()
                        ->color('info')
                        ->placeholder('Sin tipo'),
                ])
                ->filters([
                    SelectFilter::make('taxista_id')
                        ->label('Taxista')
                        ->options(fn(): array => Cache::remember('taxista_options', now()->addHours(2), function() {
                            return Taxista::where('status', 'active')
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray();
                        })),

                    SelectFilter::make('booking_department_id')
                        ->label('Departamento')
                        ->options(fn(): array => Cache::remember('department_options', now()->addHours(2), function() {
                            return BookingDepartment::where('status', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray();
                        })),

                    SelectFilter::make('tipo_id')
                        ->label('Tipo')
                        ->options(fn(): array => Cache::remember('tipo_cita_options', now()->addHours(2), function() {
                            return TipoCitas::where('status', 1)
                                ->orderBy('nombre')
                                ->pluck('nombre', 'id')
                                ->toArray();
                        })),

                    SelectFilter::make('status')
                        ->label('Estado')
                        ->options(CitaStatus::class),

                    DateRangeFilter::make('starts_at')
                        ->label('Rango de fechas'),
                ])
                ->defaultSort('starts_at', 'desc')
                ->recordActions([
                    ActionGroup::make([
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

        return $table
            // QUERY OPTIMIZADA para portal
            ->modifyQueryUsing(fn(Builder $query): Builder => $query->with([
                'department:id,name,color'
            ]))
            ->columns([
                Stack::make([
                    TextColumn::make('title')
                        ->label('Asunto')
                        ->weight(FontWeight::SemiBold)
                        ->searchable()
                        ->limit(50),

                    TextColumn::make('starts_at')
                        ->label('Fecha y hora')
                        ->dateTime('d/m/Y H:i')
                        ->color('gray'),

                    TextColumn::make('department.name')
                        ->label('Departamento')
                        ->badge()
                        ->color(fn($record): string => $record->department?->color ?? 'gray'),
                ])->space(1),

                IconSelectColumn::make('status')
                    ->label('Estado')
                    ->options(CitaStatus::class),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(CitaStatus::class),
            ])
            ->defaultSort('starts_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ])->color('gray'),
            ]);
    }

    private static function renderMesDiaHora(string $mes, string $dia, ?string $hora = null): string
    {
        $horaHtml = filled($hora)
            ? '<p class="mt-1 text-[12px] font-medium boldtext-white">' . e($hora) . '</p>'
            : '';

        $fecha = '<div class="w-[58px] shrink-0 rounded-xl border border-white/10 bg-black/20 p-2 text-center"><p class="mt-1 text-[10px] font-semibold uppercase tracking-wider  text-red-600 dark:text-white/65">' . e($mes) . '</p><p class="text-2xl font-semibold leading-none  text-red-500 dark:text-white/95 ">' . e($dia) . '</p>' . $horaHtml . '</div>';
        return $fecha;
    }
}
