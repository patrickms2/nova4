<?php

namespace App\Filament\App\Resources\Employees\Pages;

use App\Enums\CitaStatus;
use App\Filament\App\Resources\Employees\EmployeeResource;
use App\Filament\App\Resources\TaxistaAppointments\Schemas\HotelesForm;
use App\Filament\App\Resources\TaxistaAppointments\Schemas\TaxistaAppointmentForm;
use App\Filament\App\Resources\TaxistaAppointments\Tables\TaxistaAppointmentsTable;
use App\Filament\App\Resources\TaxistaAppointments\Widgets\TaxistaAppointmentStats;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Livewire;

class ManageEmployeeCitas extends ManageRelatedRecords
{
    protected static string $resource = EmployeeResource::class;

    protected static string $relationship = 'appointments';

    protected static ?string $navigationLabel = 'Citas';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();

        return (string)$record->appointments()->count();
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Agenda de citas del empleado.';
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
            CreateAction::make()
                ->label('Añadir cita')
                ->fillForm(fn(): array => [
                    'taxista_user_id' => (int)$this->getRecord()->id,
                    'created_by_user_id' => auth()->id(),
                    'status' => 'pendiente',
                ])
                ->mutateFormDataUsing(function (array $data): array {
                    $data['taxista_user_id'] = (int)$this->getRecord()->id;
                    $data['created_by_user_id'] = auth()->id();
                    $data['status'] = $data['status'] ?? 'pendiente';

                    return $data;
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return TaxistaAppointmentForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        $table = TaxistaAppointmentsTable::configure($table)
            ->modifyQueryUsing(fn($query) => $query->where('taxista_user_id', (int)$this->getRecord()->id));

        $table->getColumn('starts_at')
            ?->label('Fecha y hora')
            ?->description(fn($record) => $record->ends_at?->format('H:i') ? 'Hasta ' . $record->ends_at->format('H:i') : null);

        $table->getColumn('booking_department.name')
            ?->toggleable(isToggledHiddenByDefault: false);

        $table->getColumn('tipo.nombre')
            ?->toggleable(isToggledHiddenByDefault: false);

        return $table;
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make()
                ->label('Todas')
                ->badge(fn(): int => $this->getRecord()->appointments()->count()),
        ];

        if (enum_exists(CitaStatus::class)) {
            foreach ([CitaStatus::pendiente, CitaStatus::confirmada, CitaStatus::finalizada, CitaStatus::cancelada] as $status) {
                $tabs[$status->value] = Tab::make()
                    ->label($status->getLabel() ?? ucfirst($status->value))
                    ->badge(fn(): int => $this->getRecord()->appointments()->where('status', $status->value)->count())
                    ->badgeColor($status->getColor())
                    ->icon($status->getIcon())
                    ->modifyQueryUsing(fn(Builder $query): Builder => $query->where('status', $status->value));
            }
        }

        return $tabs;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TaxistaAppointmentStats::make([
                'createdByUserId' => auth()->id(),
            ]),
        ];
    }
}
