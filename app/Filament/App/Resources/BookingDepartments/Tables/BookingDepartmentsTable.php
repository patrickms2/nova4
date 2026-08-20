<?php

namespace App\Filament\App\Resources\BookingDepartments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingDepartmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query): Builder => $query->with(['schedules', 'creator']))
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('creator.name')
                    ->label('Encargado')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('meeting_duration')
                    ->label('Duracion')
                    ->formatStateUsing(fn(?int $state): string => ($state ?: 30) . ' min')
                    ->badge()
                    ->color('warning'),
                ColorColumn::make('color')
                    ->label('Color')
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('has_meetings_service')
                    ->label('Citas')
                    ->boolean(),

                IconColumn::make('has_documents_service')
                    ->label('Docs')
                    ->boolean(),

                IconColumn::make('has_tickets_service')
                    ->label('Tickets')
                    ->boolean(),

                TextColumn::make('schedules_count')
                    ->label('Horarios')
                    ->counts('schedules')
                    ->badge(),

                IconColumn::make('has_shifts_service')
                    ->label('Turnos')
                    ->boolean(),

                IconColumn::make('has_attendance_service')
                    ->label('Asist.')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Activo'),
                TernaryFilter::make('has_meetings_service')
                    ->label('Servicio citas'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
