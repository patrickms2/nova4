<?php

namespace App\Filament\App\Resources\Attendances;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;

class Actions
{
    public static function getActions(): array
    {
        return [
            EditAction::make()
                ->label(''),
            Action::make('checkOut')
                ->label('Check Out')
                ->icon('heroicon-o-arrow-right-on-rectangle')
                ->color('danger')
                ->button()
                ->visible(fn($record) => $record->endDate === null)
                ->requiresConfirmation()
                ->action(function ($record) {
                    $record->update(['endDate' => now()]);
                    Notification::make()
                        ->title('Check-out Successful')
                        ->success()
                        ->send();
                }),
            ActionGroup::make([
                EditAction::make(),
                ViewAction::make(),
                DeleteAction::make(),

                Action::make('duration')
                    ->label('Horas')
                    ->requiresConfirmation()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                        // Usar transacción para operaciones en lote
                        dd($records);

                        \Illuminate\Support\Facades\DB::transaction(function () use ($records) {
                            $records->each(function ($record) {
                                $duration = $record->endDate->diffInSeconds($record->startDate);
                                $record->update(['duration' => $duration]);
                            });
                        });

                        // Notificar al usuario
                        \Filament\Notifications\Notification::make()
                            ->title('Duración calculada correctamente')
                            ->success()
                            ->send();
                    }),
            ])
                ->label('Actions')
                ->icon('heroicon-o-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
        ];
    }

    public static function getBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
                BulkAction::make('duration')
                    ->label('duration')
                    ->requiresConfirmation()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                        // Usar transacción para operaciones en lote
                        \Illuminate\Support\Facades\DB::transaction(function () use ($records) {
                            $records->each(function ($record) {
                                $duration = $record->endDate->diffInSeconds($record->startDate);
                                $record->update(['duration' => $duration]);
                            });
                        });

                        // Notificar al usuario
                        \Filament\Notifications\Notification::make()
                            ->title('Duración calculada correctamente')
                            ->success()
                            ->send();
                    }),
            ]),
        ];
    }

    public static function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('monthly-view')
                ->label('View Monthly Attendance')
                ->icon('heroicon-o-calendar-days')
                ->color('primary')
                ->url(fn(): string => static::getResource()::getUrl('monthly-view')),
        ];
    }
}
