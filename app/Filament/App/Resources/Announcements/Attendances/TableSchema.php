<?php

namespace App\Filament\App\Resources\Attendances;

use App\Filament\App\Resources\Attendances\Actions;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Grouping\Group as TableGroup;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;

class TableSchema
{
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Empleado')
                    ->state(fn($record): string => (string) ($record->employee?->name ?? $record->usuario?->nombre ?? '—'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->badge()
                    ->date('d/m/Y')
                    ->color('info')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),
                Tables\Columns\TextColumn::make('startDate')
                    ->label('CheckIn')
                    ->time('h:i A')
                    ->badge()
                    ->color('success')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),
                Tables\Columns\TextColumn::make('endDate')
                    ->label('CheckOut')
                    ->time('h:i A')
                    ->badge()
                    ->color('success')
                    ->placeholder('Not checked out')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Horas')
                    ->Time("i:s")
                    ->searchable()
                    ->formatStateUsing(function ($record) {
                        if (!$record->endDate) {
                            return 'En progreso';
                        }
                        $diff = $record->startDate->diff($record->endDate);
                        return $diff->format('%h hrs %i min');
                    })
                    ->color('warning')
                    ->badge()
                    ->extraAttributes(['style' => 'font-weight: bold'])
                    ->color(
                        function ($record) {
                            if (!$record->endDate) return 'success';
                            else return 'warning';
                        })
                    ->size('lg')
                    ->extraAttributes(function ($record) {

                        $color = $record->departamento?->color;
                        return $color ?
                            ['badge' => "color: {$color}", 'style' => "color: {$color}; font-weight: 600;", 'class' => "fi-color fi-size-md fi-color-[{$color}] fi-ta-text-has-badges fi-text-color-900 dark:fi-text-color-200"] : [];
                    })
                    ->size('xxl')
                    ->extraAttributes(['style' => 'font-weight: bold'])
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('description')
                    ->label('Notas')
                    ->searchable()
                    ->limit(50)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('usuario_id')
                    ->relationship('employee', 'name')
                    ->label('Empleado'),
                Tables\Filters\SelectFilter::make('tipo_id')
                    ->relationship('tipo', 'nombre')
                    ->label('Tipo Asistencia'),
                Tables\Filters\Filter::make('date')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('checkOut')
                    ->label('Check Out')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('danger')
                    ->visible(fn($record) => $record->endDate === null)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['endDate' => now()]);
                        Notification::make()
                            ->title('Check-out Successful')
                            ->success()
                            ->send();
                    }),
            ])
            ->groups(
                [

                    TableGroup::make('employee.name')
                        ->label('Empleado')
                        ->titlePrefixedWithLabel(true)
                        ->collapsible(true),

                ]
            )
            ->recordActions(Actions::getActions())
            ->bulkActions(Actions::getBulkActions());
    }
}
