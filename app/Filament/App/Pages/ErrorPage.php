<?php

namespace App\Filament\App\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ErrorPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?string $navigationLabel = 'Error';

    protected static ?string $title = 'Página no encontrada';

    protected static ?int $navigationSort = -1;

    protected string $view = 'filament.app.pages.error-page';

    public static function shouldRegisterNavigation(): bool
    {
        return false; // No mostrar en navegación
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('help')
                ->label('Ayuda')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->modalContent(fn (): string => view('components.employee-help-popup-content', ['page' => 'error-page'])->render())
                ->modalHeading('Ayuda - Página de Error')
                ->modalFooterActions([
                    Action::make('close')
                        ->label('Entendido')
                        ->color('primary')
                        ->close(),
                ]),
            Action::make('home')
                ->label('Ir al Inicio')
                ->icon('heroicon-o-home')
                ->url('/app')
                ->color('primary'),
        ];
    }
}
