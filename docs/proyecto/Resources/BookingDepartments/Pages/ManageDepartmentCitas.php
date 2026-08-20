<?php

namespace App\Filament\App\Resources\BookingDepartments\Pages;

use App\Enums\CitaStatus;
use App\Filament\App\Resources\BookingDepartments\BookingDepartmentResource;
use App\Filament\App\Resources\BookingDepartments\Widgets\DepartmentCitasCalendar;
use App\Filament\App\Resources\TaxistaAppointments\Schemas\TaxistaAppointmentForm;
use App\Filament\App\Resources\TaxistaAppointments\Tables\TaxistaAppointmentsTable;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;

class ManageDepartmentCitas extends ManageRelatedRecords
{
    protected static string $resource = BookingDepartmentResource::class;

    protected static string $relationship = 'appointments';

    protected static ?string $navigationLabel = 'Citas';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 6;

    public bool $showCalendar = true;

    public function mount(string|int $record): void
    {
        parent::mount($record);

        // Recuperar el estado del calendario desde la sesión
        $sessionKey = 'department_calendar_visible_' . $this->getRecord()->id;
        $this->showCalendar = Session::get($sessionKey, true);
    }

    public function toggleCalendar(): void
    {
        $this->showCalendar = !$this->showCalendar;

        // Guardar el estado en la sesión para persistencia
        $sessionKey = 'department_calendar_visible_' . $this->getRecord()->id;
        Session::put($sessionKey, $this->showCalendar);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva cita')
                ->icon('heroicon-o-plus')
                ->color('danger')
                ->fillForm(fn(): array => [
                    'booking_department_id' => (int)$this->getRecord()->id,
                    'created_by_user_id' => auth()->id(),
                    'status' => 'pendiente',
                ])
                ->mutateFormDataUsing(function (array $data): array {
                    $data['booking_department_id'] = (int)$this->getRecord()->id;
                    $data['created_by_user_id'] = auth()->id();
                    $data['status'] = $data['status'] ?? 'pendiente';

                    return $data;
                }),
            Action::make('toggleCalendar')
                ->label($this->showCalendar ? 'Muestra calendario' : 'Oculta calendario')
                ->icon($this->showCalendar ? 'heroicon-o-calendar-days' : 'heroicon-o-calendar')
                ->color($this->showCalendar ? 'danger' : 'gray')
                ->action('toggleCalendar'),
            Action::make('help')
                ->label('Ayuda')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->modalContent(fn(): string => view('components.employee-help-popup-content', ['page' => 'department-citas'])->render())
                ->modalHeading('Ayuda - Citas del Departamento')
                ->modalFooterActions([
                    Action::make('close')
                        ->label('Entendido')
                        ->color('danger')
                        ->close(),
                ]),
        ];
    }

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
        return 'Citas gestionadas por este departamento.';
    }

    public function form(Schema $schema): Schema
    {
        return TaxistaAppointmentForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        $table = TaxistaAppointmentsTable::configure($table);

        $table->modifyQueryUsing(fn (Builder $query): Builder => $query->where('booking_department_id', (int) $this->getRecord()->id));

        $table->getColumn('booking_department.name')
            ?->toggleable(isToggledHiddenByDefault: true);

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
        return $this->showCalendar ? [
            DepartmentCitasCalendar::class,
        ] : [];
    }
}
