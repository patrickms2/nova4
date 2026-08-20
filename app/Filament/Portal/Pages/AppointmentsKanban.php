<?php

namespace App\Filament\Portal\Pages;

use App\Filament\Portal\Schemas\Appointments\AppointmentForm;
use App\Models\TaxiCentral\Meeting;
use App\Support\Portal\PortalTaxistaContext;
use Asmit\AdvancedKanban\Columns\KanbanColumn;
use Asmit\AdvancedKanban\Kanban;
use Asmit\AdvancedKanban\Pages\KanbanPage;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class AppointmentsKanban extends KanbanPage
{
    /** @var array<int, string> */
    public array $hiddenStatuses = [];

    protected static bool $shouldRegisterNavigation = false;

    protected static string $model = Meeting::class;

    protected static string $recordTitleAttribute = 'title';

    protected static string $recordStatusAttribute = 'status';

    protected static string $cardComponent = 'advanced-kanban::card_meeting';

    protected static ?string $title = 'Mis Citas';

    public function mount(): void
    {
        $this->hiddenStatuses = array_values(array_filter(
            session()->get($this->hiddenStatusesSessionKey(), [])
        ));
    }

    private function hiddenStatusesSessionKey(): string
    {
        $taxistaId = PortalTaxistaContext::taxistaId() ?? 'guest';

        return 'portal.kanban.appointments.hidden_statuses.' . $taxistaId;
    }

    public function handleRecordMove(string $newStatus, Model $record): void
    {
        $allowed = ['pendiente', 'confirmado', 'finalizada', 'cancelada'];

        if (!in_array($newStatus, $allowed, true)) {
            return;
        }

        $portalUserId = PortalTaxistaContext::meetingCreatorUserId();

        if (!$portalUserId || (int)$record->created_by_user_id !== (int)$portalUserId) {
            return;
        }

        $record->update(['status' => $newStatus]);

        Notification::make()
            ->title('Estado de cita actualizado')
            ->body('La cita ahora está ' . $this->resolveStatusLabel($newStatus) . '.')
            ->success()
            ->send();
    }

    public function kanban(Kanban $kanban): Kanban
    {
        $portalUserId = PortalTaxistaContext::meetingCreatorUserId();

        return $kanban
            ->model(Meeting::class)
            ->statusField('status')
            ->titleField('title')
            ->descriptionField('description')
            ->searchableFields(['title', 'description'])
            ->enableLoadingIndicator()
            ->recordsPerColumn(15)
            ->columnHeaderActions([
                \Asmit\AdvancedKanban\Actions\CreateAction::make('new')
                    ->schema(function (Schema $schema, array $arguments) {
                        return AppointmentForm::configure($schema);
                    })
                    ->action(function (array $arguments, array $data) use ($portalUserId): void {
                        if (!$portalUserId) {
                            return;
                        }

                        $data['created_by_user_id'] = $portalUserId;
                        $data['status'] = $arguments['status'] ?? 'pendiente';
                        $data['status'] = in_array($data['status'], ['pendiente', 'confirmado', 'finalizada', 'cancelada'], true)
                            ? $data['status']
                            : 'pendiente';

                        Meeting::create($data);
                    })
                    ->icon('heroicon-o-plus')
                    ->hiddenLabel()
                    ->link(),
            ])
            ->columns([
                KanbanColumn::make('pendiente')
                    ->hidden(in_array('pendiente', $this->hiddenStatuses, true))
                    ->label('Pendiente')
                    ->description('Citas pendientes')
                    ->icon('heroicon-o-clock')
                    ->iconcolor('gray')
                    ->modifyRecordQueryUsing(function ($query) use ($portalUserId) {
                        return $query
                            ->where('created_by_user_id', $portalUserId ?: 0)
                            ->where('status', 'pendiente')
                            ->orderBy('scheduled_start_at');
                    }),
                KanbanColumn::make('confirmado')
                    ->hidden(in_array('confirmado', $this->hiddenStatuses, true))
                    ->label('Confirmado')
                    ->description('Citas confirmadas')
                    ->icon('heroicon-o-check-circle')
                    ->iconcolor('info')
                    ->modifyRecordQueryUsing(function ($query) use ($portalUserId) {
                        return $query
                            ->where('created_by_user_id', $portalUserId ?: 0)
                            ->where('status', 'confirmado')
                            ->orderBy('scheduled_start_at');
                    }),
                KanbanColumn::make('finalizada')
                    ->hidden(in_array('finalizada', $this->hiddenStatuses, true))
                    ->label('Finalizada')
                    ->description('Citas finalizadas')
                    ->icon('heroicon-o-check-circle')
                    ->iconcolor('success')
                    ->modifyRecordQueryUsing(function ($query) use ($portalUserId) {
                        return $query
                            ->where('created_by_user_id', $portalUserId ?: 0)
                            ->where('status', 'finalizada')
                            ->orderByDesc('scheduled_start_at');
                    }),
                KanbanColumn::make('cancelada')
                    ->hidden(in_array('cancelada', $this->hiddenStatuses, true))
                    ->label('Cancelada')
                    ->description('Citas canceladas')
                    ->icon('heroicon-o-x-circle')
                    ->iconcolor('danger')
                    ->modifyRecordQueryUsing(function ($query) use ($portalUserId) {
                        return $query
                            ->where('created_by_user_id', $portalUserId ?: 0)
                            ->where('status', 'cancelada')
                            ->orderByDesc('scheduled_start_at');
                    }),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('table')
                ->label('Tabla')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->outlined()
                ->url(Appointments::getUrl()),
            Action::make('calendar')
                ->label('Calendario')
                ->icon('heroicon-o-calendar-days')
                ->color('gray')
                ->outlined()
                ->url(AppointmentsCalendar::getUrl()),
            CreateAction::make('add_appointment')
                ->label('Añadir')
                ->icon('heroicon-o-plus')
                ->successNotificationTitle('Cita creada')
                ->model(Meeting::class)
                ->schema(fn(Schema $schema) => AppointmentForm::configure($schema))
                ->fillForm(function (): array {
                    return [
                        'created_by_user_id' => PortalTaxistaContext::meetingCreatorUserId(),
                        'status' => 'pendiente',
                    ];
                })
                ->mutateDataUsing(function (array $data): array {
                    $data['created_by_user_id'] = PortalTaxistaContext::meetingCreatorUserId();
                    $data['status'] = $data['status'] ?? 'pendiente';

                    return $data;
                }),
            Action::make('hide_columns')
                ->label('Ocultar')
                ->icon('heroicon-o-eye-slash')
                ->color('gray')
                ->outlined()
                ->fillForm(fn(): array => [
                    'hidden_statuses' => $this->hiddenStatuses,
                ])
                ->schema([
                    ToggleButtons::make('hidden_statuses')
                        ->label('Estados a ocultar')
                        ->multiple()
                        ->inline()
                        ->options([
                            'pendiente' => 'Pendiente',
                            'confirmado' => 'Confirmado',
                            'finalizada' => 'Finalizada',
                            'cancelada' => 'Cancelada',
                        ]),
                ])
                ->action(function (array $data): void {
                    $this->hiddenStatuses = array_values(array_filter($data['hidden_statuses'] ?? []));
                    session()->put($this->hiddenStatusesSessionKey(), $this->hiddenStatuses);

                    Notification::make()
                        ->title('Columnas actualizadas')
                        ->body('Se ocultaron ' . count($this->hiddenStatuses) . ' estado(s) en Kanban.')
                        ->success()
                        ->send();
                }),
        ];
    }

    private function resolveStatusLabel(string $status): string
    {
        return match ($status) {
            'confirmado' => 'confirmada',
            'finalizada' => 'finalizada',
            'cancelada' => 'cancelada',
            default => 'pendiente',
        };
    }
}
