<?php

namespace App\Filament\App\Resources\Employees\Tables;

use App\Filament\App\Resources\Employees\EmployeeResource;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Archilex\AdvancedTables\AdvancedTables;

class EmployeesTable
{
    use AdvancedTables;

    protected static function formatPendingAlerts(
        int $timeOffCount,
        int $swapCount,
        int $appointmentCount,
        int $ticketCount,
    ): string {
        $alerts = [];

        if ($timeOffCount > 0) {
            $alerts[] = $timeOffCount . ' ausencia' . ($timeOffCount > 1 ? 's' : '');
        }

        if ($swapCount > 0) {
            $alerts[] = $swapCount . ' permiso' . ($swapCount > 1 ? 's' : '');
        }

        if ($appointmentCount > 0) {
            $alerts[] = $appointmentCount . ' cita' . ($appointmentCount > 1 ? 's' : '');
        }

        if ($ticketCount > 0) {
            $alerts[] = $ticketCount . ' ticket' . ($ticketCount > 1 ? 's' : '');
        }

        return implode(' · ', $alerts) ?: '—';
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->deferFilters(false)
            ->persistFiltersInSession(false)
            ->modifyQueryUsing(fn(Builder $query): Builder => $query
                ->orderByDesc('status')
                ->orderBy('name'))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn(bool $state): string => $state ? 'Activo' : 'Baja')
                    ->color(fn(bool $state): string => $state ? 'success' : 'gray')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(['id', 'name', 'email', 'nif', 'phone', 'employee_code'])
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(['email'])
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                                    TextColumn::make('nif')
                    ->label('NIF')
                    ->searchable(['nif'])
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                                    TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable(['phone'])
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('bookingDepartment.name')
                    ->label('Departamento')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('employeeShifts_count')
                    ->label('Turnos')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('todayEmployeeShift.shift_code')
                    ->label('Turno hoy')
                    ->state(fn(User $record): string => (string) ($record->todayEmployeeShift?->centralTurno?->name
                        ?? $record->todayEmployeeShift?->shift_code
                        ?? '—'))
                    ->badge()
                    ->color(fn(User $record): string => $record->todayEmployeeShift ? 'warning' : 'gray'),

                TextColumn::make('pending_alerts')
                    ->label('Pendientes')
                    ->state(fn(User $record): string => self::formatPendingAlerts(
                        (int) ($record->pending_timeoff_count ?? 0),
                        (int) ($record->pending_swaps_count ?? 0),
                        (int) ($record->pending_appointments_count ?? 0),
                        (int) ($record->open_tickets_count ?? 0),
                    ))
                    ->badge()
                    ->color(fn(User $record): string => (($record->pending_timeoff_count ?? 0) + ($record->pending_swaps_count ?? 0) + ($record->pending_appointments_count ?? 0) + ($record->open_tickets_count ?? 0)) > 0 ? 'danger' : 'gray')
                    ->sortable(query: fn(Builder $query, string $direction): Builder => $query->orderByRaw('(COALESCE(pending_timeoff_count, 0) + COALESCE(pending_swaps_count, 0) + COALESCE(pending_appointments_count, 0) + COALESCE(open_tickets_count, 0)) ' . $direction)),

                TextColumn::make('managed_pending_alerts')
                    ->label('Pendientes depto.')
                    ->state(fn(User $record): string => self::formatPendingAlerts(
                        (int) ($record->managed_pending_timeoff_count ?? 0),
                        (int) ($record->managed_pending_swaps_count ?? 0),
                        (int) ($record->managed_pending_appointments_count ?? 0),
                        (int) ($record->managed_open_tickets_count ?? 0),
                    ))
                    ->badge()
                    ->color(fn(User $record): string => (($record->managed_pending_timeoff_count ?? 0) + ($record->managed_pending_swaps_count ?? 0) + ($record->managed_pending_appointments_count ?? 0) + ($record->managed_open_tickets_count ?? 0)) > 0 ? 'warning' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('updated_at')
                    ->label('Última actividad')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('booking_department_id')
                    ->label('Departamento')
                    ->relationship('bookingDepartment', 'name')
                    ->searchable()
                    ->default(16)
                    ->preload(),

                TernaryFilter::make('status')
                    ->label('Activo')
                    ->trueLabel('Activos')
                    ->falseLabel('Baja')
                    ->placeholder('Todos'),
                
                // Filtro para empleados con swaps pendientes
                TernaryFilter::make('has_pending_swaps')
                    ->label('Swaps Pendientes')
                    ->trueLabel('Con swaps pendientes')
                    ->falseLabel('Sin swaps pendientes')
                    ->placeholder('Todos')
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'] ?? null)) {
                            return $query;
                        }

                        $value = filter_var($data['value'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                        if ($value === true) {
                            return $query->whereHas('shiftSwapRequests', function (Builder $q) {
                                $q->where('status', 'pending');
                            });
                        } elseif ($value === false) {
                            return $query->whereDoesntHave('shiftSwapRequests', function (Builder $q) {
                                $q->where('status', 'pending');
                            });
                        }

                        return $query;
                    }),
                
                // Filtro para empleados con permisos pendientes
                TernaryFilter::make('has_pending_timeoff') 
                    ->label('Permisos Pendientes')
                    ->trueLabel('Con permisos pendientes')
                    ->falseLabel('Sin permisos pendientes')
                    ->placeholder('Todos')
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'] ?? null)) {
                            return $query;
                        }

                        $value = filter_var($data['value'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                        if ($value === true) {
                            return $query->whereHas('employeeTimeOff', function (Builder $q) {
                                $q->where('status', 'pending');
                            });
                        } elseif ($value === false) {
                            return $query->whereDoesntHave('employeeTimeOff', function (Builder $q) {
                                $q->where('status', 'pending');
                            });
                        }

                        return $query;
                    }),
                
                // Filtro para empleados con permisos aprobados
                TernaryFilter::make('has_approved_timeoff')
                    ->label('Permisos Aprobados')
                    ->trueLabel('Con permisos aprobados')
                    ->falseLabel('Sin permisos aprobados')
                    ->placeholder('Todos')
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'] ?? null)) {
                            return $query;
                        }

                        $value = filter_var($data['value'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                        if ($value === true) {
                            return $query->whereHas('employeeTimeOff', function (Builder $q) {
                                $q->where('status', 'approved');
                            });
                        } elseif ($value === false) {
                            return $query->whereDoesntHave('employeeTimeOff', function (Builder $q) {
                                $q->where('status', 'approved');
                            });
                        }

                        return $query;
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                \Filament\Actions\Action::make('ayuda')
                    ->label('Ayuda')
                    ->icon('heroicon-o-question-mark-circle')
                    ->color('gray')
                    ->tooltip('Ver secciones disponibles')
                    ->url(fn(User $record): string => EmployeeResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(false),

                \Filament\Actions\Action::make('turnos')
                    ->label('Turnos')
                    ->icon('heroicon-o-calendar-days')
                    ->color('gray')
                    ->url(fn(User $record): string => EmployeeResource::getUrl('turnos', ['record' => $record])),

                \Filament\Actions\Action::make('calendario')
                    ->label('Calendario')
                    ->icon('heroicon-o-calendar')
                    ->color('gray')
                    ->url(fn(User $record): string => EmployeeResource::getUrl('calendario', ['record' => $record])),

                \Filament\Actions\Action::make('asistencias')
                    ->label('Asistencias')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->url(fn(User $record): string => EmployeeResource::getUrl('asistencias', ['record' => $record])),

                \Filament\Actions\Action::make('citas')
                    ->label('Citas')
                    ->icon('heroicon-o-calendar-days')
                    ->color('gray')
                    ->badge(fn(User $record): int => $record->appointments_count ?? 0)
                    ->badgeColor('warning')
                    ->url(fn(User $record): string => EmployeeResource::getUrl('citas', ['record' => $record])),

                \Filament\Actions\Action::make('documentos')
                    ->label('Documentos')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->badge(fn(User $record): int => $record->documents_count ?? 0)
                    ->badgeColor('info')
                    ->url(fn(User $record): string => EmployeeResource::getUrl('documentos', ['record' => $record])),

                \Filament\Actions\Action::make('tickets')
                    ->label('Tickets')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('gray')
                    ->badge(fn(User $record): int => $record->tickets_count ?? 0)
                    ->badgeColor('danger')
                    ->url(fn(User $record): string => EmployeeResource::getUrl('tickets', ['record' => $record])),

                \Filament\Actions\Action::make('vacaciones')
                    ->label('Vacaciones')
                    ->icon('heroicon-o-sun')
                    ->color('gray')
                    ->badge(fn(User $record): int => $record->employee_time_off_count ?? 0)
                    ->badgeColor('primary')
                    ->url(fn(User $record): string => EmployeeResource::getUrl('vacaciones', ['record' => $record])),

                \Filament\Actions\Action::make('permisos')
                    ->label('Permisos')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('gray')
                    ->badge(fn(User $record): int => $record->shift_swap_requests_count ?? 0)
                    ->badgeColor('info')
                    ->url(fn(User $record): string => EmployeeResource::getUrl('permisos', ['record' => $record])),
            ])
            ->paginated([25, 50, 100, 'all']);;
    }
}
