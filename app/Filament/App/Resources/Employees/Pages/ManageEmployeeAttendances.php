<?php

namespace App\Filament\App\Resources\Employees\Pages;

use App\Filament\App\Resources\Attendances\FormSchema as AttendanceFormSchema;
use App\Filament\App\Resources\Attendances\TableSchema as AttendanceTableSchema;
use App\Filament\App\Resources\Employees\EmployeeResource;
use App\Models\Taxi\Attendance;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Livewire;

class ManageEmployeeAttendances extends ManageRelatedRecords
{
    protected static string $resource = EmployeeResource::class;

    protected static string $relationship = 'attendances';

    protected static ?string $navigationLabel = 'Asistencias';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();

        return (string)$record->attendances()
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->count();
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Registro de asistencia del empleado.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('empleados')
                ->label('Empleados')
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

                    return '/app/team/' . $tenantId . '/employees';
                }),
            
            \Filament\Actions\Action::make('checkIn')
                ->label('Check In')
                ->icon('heroicon-o-arrow-left-on-rectangle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function (): void {
                    $userId = (int)$this->getRecord()->id;

                    $existing = Attendance::query()
                        ->where('usuario_id', $userId)
                        ->whereDate('date', today())
                        ->whereNull('endDate')
                        ->first();

                    if ($existing) {
                        Notification::make()
                            ->title('Ya registrado')
                            ->body('Ya tiene una entrada activa hoy.')
                            ->warning()
                            ->send();

                        return;
                    }

                    Attendance::create([
                        'usuario_id' => $userId,
                        'date' => today(),
                        'startDate' => now()->format('H:i:s'),
                        'status' => 'presente',
                    ]);

                    Notification::make()
                        ->title('Check-in registrado')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return AttendanceFormSchema::form($schema);
    }

    public function table(Table $table): Table
    {
        return AttendanceTableSchema::table($table)
            ->modifyQueryUsing(fn($query) => $query->where('usuario_id', (int)$this->getRecord()->id));
    }
}
