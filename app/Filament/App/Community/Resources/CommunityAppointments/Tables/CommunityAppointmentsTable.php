<?php

namespace App\Filament\App\Community\Resources\CommunityAppointments\Tables;

use App\Models\CommunityAppointment;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CommunityAppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at')->columns([
                TextColumn::make('starts_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(), TextColumn::make('title')->label('Cita')->searchable(),
                TextColumn::make('person.display_name')->label('Propietario')->searchable(), TextColumn::make('community.name')->label('Comunidad'),
                TextColumn::make('department.name')->label('Departamento')->badge(), TextColumn::make('status')->label('Estado')->badge(),
            ])->filters([
                SelectFilter::make('community')->relationship('community', 'name')->searchable()->preload(), SelectFilter::make('status')->options(['scheduled' => 'Pendiente', 'confirmed' => 'Confirmada', 'completed' => 'Finalizada', 'cancelled' => 'Cancelada']),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('confirm')
                    ->label('Confirmar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (CommunityAppointment $record): bool => $record->status === 'scheduled')
                    ->action(fn (CommunityAppointment $record) => $record->update(['status' => 'confirmed']))
                    ->successNotificationTitle('Cita confirmada'),
                Action::make('complete')
                    ->label('Atendida')
                    ->icon('heroicon-o-check-badge')
                    ->visible(fn (CommunityAppointment $record): bool => $record->status === 'confirmed')
                    ->action(fn (CommunityAppointment $record) => $record->update(['status' => 'completed']))
                    ->successNotificationTitle('Cita marcada como atendida'),
                Action::make('cancel')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (CommunityAppointment $record): bool => in_array($record->status, ['scheduled', 'confirmed'], true))
                    ->action(fn (CommunityAppointment $record) => $record->update(['status' => 'cancelled']))
                    ->successNotificationTitle('Cita cancelada'),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
