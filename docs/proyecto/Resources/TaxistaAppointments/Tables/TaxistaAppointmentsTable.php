<?php

namespace App\Filament\App\Resources\TaxistaAppointments\Tables;

use App\Models\BookingDepartment;
use App\Models\Taxista;
use App\Models\Taxi\TipoCitas;

use App\Enums\CitaStatus;
use App\Support\DepartmentManagerAccess;
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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Tables\Grouping\Group as TableGroup;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use UnitEnum;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class TaxistaAppointmentsTable
{
    public static function configure(Table $table): Table
    {
        if (!PortalTaxistaContext::isPortalPanel()) {
            return $table
                ->modifyQueryUsing(fn(Builder $query): Builder => $query
                    ->with(['taxista', 'booking_department', 'tipo'])
                    ->orderBy('starts_at', 'desc'))
                ->groups([
                    TableGroup::make('taxista.name')
                        ->label('Usuario')
                        ->titlePrefixedWithLabel(true)
                        ->collapsible(true),
                    TableGroup::make('booking_department.name')
                        ->label('Departamento')
                        ->titlePrefixedWithLabel(true)
                        ->collapsible(true),
                    TableGroup::make('tipo.nombre')
                        ->label('Tipo')
                        ->titlePrefixedWithLabel(true)
                        ->collapsible(true),
                    TableGroup::make('status')
                        ->label('Estado')
                        ->titlePrefixedWithLabel(false)
                        ->collapsible(true),
                ])
                ->columns([
                    TextColumn::make('starts_at')
                        ->label('Fecha')
                        //->dateTime('d/m/Y H:i')
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
                        ->visible(fn(Get $get): bool => !PortalTaxistaContext::isPortalPanel()),


                    /*TextColumn::make('ends_at')
                        ->label('Fin')
                        ->dateTime('d/m/Y H:i')
                        ->color('gray')
                        ->toggleable(isToggledHiddenByDefault: true),*/

                    TextColumn::make('booking_department.name')
                        ->label('Departamento')
                        ->badge()
                        ->extraAttributes(['style' => 'font-weight: bold'])
                        ->color(
                            fn($record): string => BookingDepartment::find($record->booking_department_id)->color ?? 'info'

                        )
                        ->placeholder('Sin departamento'),


                    TextColumn::make('tipo.nombre')
                        ->label('Tipo')
                        ->badge()
                        ->extraAttributes(['style' => 'font-weight: bold'])
                        ->color(
                            fn($record): string => TipoCitas::find($record->tipo_cita_id)->color ?? 'info'

                        )
                        ->placeholder('Sin tipo'),

                    IconSelectColumn::make('status')
                        ->label('Estado')
                        ->options(CitaStatus::class),


                ])
                ->defaultSort('starts_at', 'desc')
                ->recordActions([
                    ActionGroup::make([
                        EditAction::make()
                            ->slideOver()
                            ->color('warning')
                            ->after(function ($livewire): void {
                                $livewire->resetTable();
                            }),
                        DeleteAction::make()
                            ->after(function ($livewire): void {
                                $livewire->resetTable();
                            }),
                    ])->color('gray'),
                ])
                ->toolbarActions([
                    BulkActionGroup::make([
                        DeleteBulkAction::make(),
                        BulkAction::make('activate')
                            ->label('Confirmar')
                            ->icon('heroicon-m-check-circle')
                            ->requiresConfirmation()
                            ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                                $records->each(function ($record) {
                                    $record->update(['status' => 'confirmada']);
                                });

                                Notification::make()
                                    ->title('Citas confirmadas')
                                    ->success()
                                    ->send();
                            }),
                        BulkAction::make('desactivate')
                            ->label('Cancelar')
                            ->icon('heroicon-m-x-circle')
                            ->requiresConfirmation()
                            ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                                $records->each(function ($record) {
                                    $record->update(['status' => 'cancelada']);
                                });

                                Notification::make()
                                    ->title('Citas canceladas')
                                    ->success()
                                    ->send();
                            }),
                        BulkAction::make('finalizar')
                            ->label('Finalizar')
                            ->icon('heroicon-m-x-circle')
                            ->requiresConfirmation()
                            ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                                $records->each(function ($record) {
                                    $record->update(['status' => 'finalizada']);
                                });

                                Notification::make()
                                    ->title('Citas finalizadas')
                                    ->success()
                                    ->send();
                            }),
                        BulkAction::make('pendiente')
                            ->label('Pendiente')
                            ->icon('heroicon-m-x-circle')
                            ->requiresConfirmation()
                            ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                                $records->each(function ($record) {
                                    $record->update(['status' => 'pendiente']);
                                });

                                Notification::make()
                                    ->title('Citas pendientes')
                                    ->success()
                                    ->send();
                            }),
                        BulkAction::make('Cambiar Tipo')
                            ->icon('heroicon-m-pencil-square')
                            ->form([
                                Select::make('tipo_cita_id')
                                    ->label('Tipos')
                                    ->relationship('tipo', 'nombre')
                                    ->required(),
                            ])
                            ->action(function (Collection $records, array $data): void {
                                $records->each->update(['tipo_id' => $data['tipo_id']]);
                            })
                            ->deselectRecordsAfterCompletion(),

                        BulkAction::make('Cambiar Usuario')
                            ->icon('heroicon-m-pencil-square')
                            ->form([
                                Select::make('taxista_user_id')
                                    ->label('Usuarios')
                                    ->relationship('taxista', 'nombre')
                                    ->required(),
                            ])
                            ->action(function (Collection $records, array $data): void {
                                $records->each->update(['taxista_user_id' => $data['taxista_user_id']]);
                            })
                            ->deselectRecordsAfterCompletion(),
                    ]),
                ])
                ->filters([

                    SelectFilter::make('taxista_user_id')
                        ->label('Usuario')
                        ->relationship('taxista', 'name')
                        ->searchable()
                        //->hiddenOn([App\Filament\Clusters\Taxistas\Taxistas\RelationManagers\TicketsRelationManager::class])
                        ->preload(),

                    SelectFilter::make('booking_department_id')
                        ->label('Departamento')
                        ->relationship('booking_department', 'name')
                        ->preload(),
                    SelectFilter::make('tipo_cita_id')
                        ->label('Tipo')
                        ->relationship('tipo', 'nombre')
                        ->preload(),
                    SelectFilter::make('status')
                        ->label('Estado')
                        ->options(CitaStatus::options())
                        ->preload(),

                    DateRangeFilter::make('appointment_date')
                        ->label('Fecha')
                    //->defaultCustom(Carbon::now()->today(), Carbon::now()->endOfMonth())
                    //,

                ], layout: FiltersLayout::Modal)
                ->filtersFormColumns(4)
                ->filtersFormWidth(Width::FourExtraLarge)
                ->recordActions([
                    EditAction::make()
                        ->label('')
                        ->slideOver()
                        ->color('warning')
                        ->modalWidth(Width::FourExtraLarge)
                        ->modalHeading('Editar cita')
                        ->modalIcon(Heroicon::CalendarDays)
                        ->modalIconColor('warning')
                        ->modalSubmitAction(fn (Action $action): Action => $action->color('warning'))
                        ->after(function ($livewire): void {
                            $livewire->resetTable();
                        }),
                    ViewAction::make()
                        ->link()
                        ->hiddenLabel()
                        ->modalWidth(Width::Large)
                        ->modalIcon(Heroicon::CalendarDays)
                        ->modalIconColor('warning')
                        ->modalHeading(fn ($record): string => (string) ($record->title ?: 'Detalle de cita'))
                        ->modalDescription(fn ($record): string => (string) ($record->starts_at?->format('d/m/Y H:i') ?: 'Sin fecha'))
                        ->icon(Heroicon::OutlinedChevronRight),
                    /*Action::make('mark_complete')
                        ->label('')
                        ->tooltip('Confirmar')
                        ->icon('heroicon-m-calendar')
                        ->color('success')
                        ->visible(fn($record) => ($record->status !== CitaStatus::confirmada))
                        ->modalWidth(Width::Medium)
                        ->modalSubmitActionLabel('Marcar confirmada')
                        ->action(function (Cita $record) {

                            CitaUpdater::markConfirm(Cita::class, $record->id);

                            Notification::make()
                                ->body('Cita confirmada')
                                ->color('success')
                                ->send();
                        }),

                    Action::make('mark_pendiente')
                        ->label('')
                        ->tooltip('Pendiente')
                        ->icon('heroicon-m-clock')
                        ->color('warning')
                        ->visible(fn($record) => ($record->status !== CitaStatus::pendiente))
                        ->modalWidth(Width::Medium)
                        ->modalSubmitActionLabel('Marcar pendiente')
                        ->action(function (Cita $record) {
                            CitaUpdater::markOpen(Cita::class, $record->id);

                            Notification::make()
                                ->body('Cita marcado como pendiente')
                                ->color('success')
                                ->send();
                        }),
                    Action::make('mark_completa')
                        ->label('')
                        ->tooltip('Completar')
                        ->icon('heroicon-m-check-circle')
                        ->color('warning')
                        ->visible(fn($record) => ($record->status !== CitaStatus::completa))
                        ->modalWidth(Width::Medium)
                        ->modalSubmitActionLabel('Marcar pendiente')
                        ->action(function (Cita $record) {
                            CitaUpdater::markCompleted(Cita::class, $record->id);

                            Notification::make()
                                ->body('Cita marcado como completa')
                                ->color('success')
                                ->send();
                        }),

                    Action::make('mark_cancel')
                        ->label('')
                        ->tooltip('Cancelar')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->visible(fn($record) => ($record->status !== CitaStatus::cancelada))
                        ->modalWidth(Width::Medium)
                        ->modalSubmitActionLabel('Marcar Cancelada')
                        ->action(function (Cita $record) {
                            // CitaUpdater::markOpen(Cita::class,$record->id);

                            // CitaUpdater::markCompleted(Cita::class,$record->id);

                            CitaUpdater::markCancel(Cita::class, $record->id);

                            Notification::make()
                                ->body('Cita marcado como cancelada')
                                ->color('success')
                                ->send();
                        }),*/

                ])
                ->headerActions([ // Agregar acciones en el encabezado de la tabla
                    // CreateAction::make(), // Botón para el formulario de creación con slideOver
                ]);
        }

        return $table
            ->modifyQueryUsing(fn(Builder $query): Builder => $query->with(['taxista', 'department', 'calendar', 'tipo']))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('taxista.name')
                    ->label('Taxista')
                    ->searchable()
                    ->sortable()
                    ->visible(fn(Get $get): bool => !PortalTaxistaContext::isPortalPanel()),

                TextColumn::make('appointment_date')
                    ->label('Asunto')
                    ->searchable()
                    ->sortable()
                    ->state(function ($record): string {
                        $appointmentDate = $record->appointment_date ?? $record->starts_at;

                        return self::renderMesDiaHora(
                            $appointmentDate?->format('M') ?? '--',
                            $appointmentDate?->format('d') ?? '--',
                            $record->starts_at?->format('H:i') ?? '--:--'
                        );
                    })
                    ->html()
                    ->visible(true),
                TextColumn::make('booking_department.name')
                    ->label('Departamento')
                    ->searchable()
                    ->sortable()
                    ->state(fn($record): string => self::renderTitleWithDepartmentBadge(
                        (string)($record->title ?? 'Sin asunto'),
                        $record->department?->name ?? 'Sin departamento',
                        $record->department?->color,
                    ))
                    ->html()
                    ->visible(true),
                TextColumn::make('tipo.nombre')
                    ->label('Tipo')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('starts_at')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('hora')
                    ->label('Hora')
                    ->state(fn($record): ?string => $record->starts_at?->format('H:i'))
                    ->toggleable(isToggledHiddenByDefault: false),


            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'confirmada' => 'Confirmada',
                        'finalizada' => 'Finalizada',
                        'cancelada' => 'Cancelada',
                    ]),
                SelectFilter::make('booking_department_id')
                    ->label('Departamento')
                    ->options(fn(): array => DepartmentManagerAccess::scopeManagedServiceDepartments(
                        BookingDepartment::query()->orderBy('name'),
                        'has_meetings_service',
                        column: 'id',
                    )->pluck('name', 'id')->toArray()),
            ])
            ->defaultSort('starts_at', 'desc')
            ->contentGrid([
                'sm' => 1,
                'md' => 2,
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->slideOver()
                        ->after(function ($livewire): void {
                            $livewire->resetTable();
                        }),
                    DeleteAction::make()
                        ->after(function ($livewire): void {
                            $livewire->resetTable();
                        }),
                ])->color('gray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function renderBadge(string $label, ?string $color): string
    {
        $badgeColor = filled($color) ? $color : '#6b7280';
        $escapedLabel = e($label);
        $escapedColor = e($badgeColor);

        return "<span class=\"inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset\" style=\"--tw-ring-color: {$escapedColor}; color: {$escapedColor};\">{$escapedLabel}</span>";
    }

    private static function renderTitleWithDepartmentBadge(string $title, string $department, ?string $color): string
    {
        $escapedTitle = e($title);

        return "<div>{$escapedTitle}</div><div class=\"mt-1\">" . self::renderBadge($department, $color) . '</div>';
    }

    private static function renderMesDiaHora(string $mes, string $dia, ?string $hora = null): string
    {
        $horaHtml = filled($hora)
            ? '<p class="mt-1 text-[10px] font-medium text-white/70">' . e($hora) . '</p>'
            : '';

        $fecha = '<div class="w-[58px] shrink-0 rounded-xl border border-white/10 bg-black/20 p-2 text-center"><p class="mt-1 text-[10px] font-semibold uppercase tracking-wider  text-red-600 dark:text-white/65">' . e($mes) . '</p><p class="text-2xl font-semibold leading-none  text-red-500 dark:text-white/95 ">' . e($dia) . '</p>' . $horaHtml . '</div>';
        return $fecha;
    }
}
