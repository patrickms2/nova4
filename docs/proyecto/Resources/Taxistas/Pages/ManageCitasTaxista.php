<?php

namespace App\Filament\App\Resources\Taxistas\Pages;

use App\Enums\CitaStatus;
use App\Filament\App\Resources\TaxistaAppointments\Schemas\TaxistaAppointmentForm;
use App\Filament\App\Resources\TaxistaAppointments\Schemas\TaxistaAppointmentInfolist;
use App\Filament\App\Resources\TaxistaAppointments\Tables\TaxistaAppointmentsTable;
use App\Filament\App\Resources\TaxistaAppointments\Widgets\TaxistaAppointmentStats;
use App\Filament\App\Resources\Taxistas\TaxistaResource;
use App\Models\TaxiCentral\Department;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Livewire;

class ManageCitasTaxista extends ManageRelatedRecords
{
    protected static string $resource = TaxistaResource::class;

    protected static string $relationship = 'appointments';

    protected static ?string $navigationLabel = 'Citas';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();

        return (string) $record->appointments()->count();
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Agenda de citas del taxista.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Añadir cita')
                ->color('warning')
                ->slideOver()
                ->modalWidth('4xl')
                ->modalHeading('Añadir cita')
                ->modalIcon('heroicon-o-calendar-days')
                ->modalIconColor('warning')
                ->modalSubmitAction(fn ($action) => $action->color('warning'))
                ->fillForm(fn (): array => [
                    'taxista_user_id' => $this->resolveTaxistaUserId(),
                    'booking_department_id' => null,
                    'created_by_user_id' => auth()->id(),
                    'status' => 'pendiente',
                ])
                ->mutateFormDataUsing(function (array $data): array {
                    if ($taxistaUserId = $this->resolveTaxistaUserId()) {
                        $data['taxista_user_id'] = $taxistaUserId;
                    }

                    $data['created_by_user_id'] = auth()->id();

                    $data['status'] = $data['status'] ?? CitaStatus::pendiente->value;

                    return $data;
                })
                ->successRedirectUrl(fn () => request()->header('referer')),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return TaxistaAppointmentForm::configure($schema);
    }

    public function infolist(Schema $schema): Schema
    {
        return TaxistaAppointmentInfolist::configure($schema);
    }

    public function table(Table $table): Table
    {
        $table = TaxistaAppointmentsTable::configure($table)
            ->modifyQueryUsing(function ($query) {
                $taxistaUserId = $this->resolveTaxistaUserId();

                return $taxistaUserId
                    ? $query->where('taxista_user_id', $taxistaUserId)
                    : $query->whereRaw('1 = 0');
            });
        // $table->getColumn('created_by_user_id')->toggleable(isToggledHiddenByDefault: true);

        $table->getColumn('starts_at')
            ?->label('Fecha y hora')
            ?->description(fn ($record) => $record->ends_at?->format('H:i') ? 'Hasta '.$record->ends_at->format('H:i') : null);

        $table->getColumn('booking_department.name')
            ?->toggleable(isToggledHiddenByDefault: false);

        $table->getColumn('tipo.nombre')
            ?->toggleable(isToggledHiddenByDefault: false);

        return $table;
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->label('Todas')
                ->badge(fn (): int => $this->getRecord()->appointments()->count()),
            'pendiente' => $this->makeStatusTab(CitaStatus::pendiente),
            'confirmada' => $this->makeStatusTab(CitaStatus::confirmada),
            'finalizada' => $this->makeStatusTab(CitaStatus::finalizada),
            'cancelada' => $this->makeStatusTab(CitaStatus::cancelada),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TaxistaAppointmentStats::make([
                'createdByUserId' => auth()->id(),
            ]),
        ];
    }

    private function resolveTaxistaUserId(): ?int
    {
        $owner = $this->getRecord();

        return $owner?->id ? (int) $owner->id : null;
    }

    private function resolveOwnerUserId(): ?int
    {
        $owner = $this->getRecord();
        $userId = $owner?->portal_user_id;

        return $userId ? (int) $userId : null;
    }

    private function resolveDefaultDepartmentId(): ?int
    {
        $departmentId = Department::query()
            ->meetingBookable()
            ->orderBy('name')
            ->value('id');

        return $departmentId ? (int) $departmentId : null;
    }

    private function makeStatusTab(CitaStatus $status): Tab
    {
        return Tab::make()
            ->label($status->getLabel() ?? ucfirst($status->value))
            ->badge(fn (): int => $this->getRecord()->appointments()
                ->where('status', $status->value)
                ->count())
            ->badgeColor($status->getColor())
            ->icon($status->getIcon())
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', $status->value));
    }
}
