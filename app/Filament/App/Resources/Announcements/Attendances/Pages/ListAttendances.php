<?php

namespace App\Filament\App\Resources\Attendances\Pages;

use App\Filament\App\Resources\Attendances\Attendances\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Tables;
use Archilex\AdvancedTables\AdvancedTables;
use Archilex\AdvancedTables\Components\PresetView;

class ListAttendances extends ListRecords
{


    protected static string $resource = AttendanceResource::class;
    protected static ?string $title = 'Reg. de Asistencias';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('empleados')
                ->label('Cuadrante E/S')
                ->icon('heroicon-s-user')
                ->color('primary')
                ->url(function () {
                    // Extraer tenant ID de la URL actual
                    $currentUrl = request()->url();
                    $tenantId = '1'; // fallback

                    // Buscar el patrón /app/team/{tenant}/ en la URL actual
                    if (preg_match('/\/app\/team\/([^\/]+)\//', $currentUrl, $matches)) {
                        $tenantId = $matches[1];
                    }

                    return '/app/team/' . $tenantId . '/attendance-roster';
                }),
            /*Action::make('monthly-view')
                ->label('Vista Mensual')
                ->icon('heroicon-o-calendar-days')
                ->color('primary')
                ->url(fn (): string => static::getResource()::getUrl('monthly-view')),*/
            CreateAction::make("Asistencia")
                ->label('Nuevo Registro')
                ->icon('heroicon-o-clock')
                ->color('danger')
                ->slideOver(), // Botón para el formulario de creación con slideOver
        ];
    }
}
