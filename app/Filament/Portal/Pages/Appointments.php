<?php

namespace App\Filament\Portal\Pages;

use App\Enums\Icons\PhosphorIcons;
use App\Filament\App\Resources\TaxistaAppointments\Schemas\TaxistaAppointmentForm;
use App\Filament\App\Resources\TaxistaAppointments\Schemas\TaxistaAppointmentInfolist;
use App\Filament\Portal\Schemas\Appointments\AppointmentForm;
use App\Filament\Portal\Schemas\Appointments\AppointmentInfolist;
use App\Models\TaxiCentral\Meeting;
use App\Models\TaxistaAppointment;
use App\Support\Portal\PortalTaxistaContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use UnitEnum;

class Appointments extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.portal.pages.appointments';

    protected static string|BackedEnum|null $navigationIcon = PhosphorIcons::CalendarDots;

    protected static ?string $navigationLabel = 'Citas';

    protected static string|UnitEnum|null $navigationGroup = 'Mi Portal';

    protected ?string $heading = 'Mis Citas';

    protected static ?int $navigationSort = 3;

    public function getSubheading(): string|Htmlable|null
    {
        return 'Agenda personal de citas.';
    }

    public function table(Table $table): Table
    {
        $portalUserId = PortalTaxistaContext::meetingCreatorUserId();

        return $table
            ->selectable(false)
            ->query(
                TaxistaAppointment::query()
                    ->when($portalUserId, fn($query) => $query->where('created_by_user_id', $portalUserId), fn($query) => $query->whereRaw('1 = 0'))
            )
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('title')
                    ->label('Cita')
                    ->html()
                    ->state(function ($record): HtmlString {
                        $title = e((string)($record->title ?: 'Sin motivo'));
                        $tipo = e((string)($record->tipo?->nombre ?? 'Sin tipo'));
                        $department = e((string)($record->department?->name ?? 'Sin departamento'));
                        $start = e((string)($record->scheduled_start_at?->format('d/m/Y H:i') ?? 'Sin fecha'));
                        $end = e((string)($record->scheduled_end_at?->format('H:i') ?? '-'));
                        $status = e(ucfirst((string)($record->status ?? 'pendiente')));
                        $statusClass = match ($record->status) {
                            'confirmado' => 'portal-badge-info',
                            'finalizada' => 'portal-badge-success',
                            'cancelada' => 'portal-badge-danger',
                            default => 'portal-badge-gray',
                        };

                        return new HtmlString(
                            "<div class='portal-row-tile'>
                                <div class='portal-row-tile__head'>
                                    <p class='portal-row-tile__title'>{$title}</p>
                                    <span class='portal-row-tile__badge {$statusClass}'>{$status}</span>
                                </div>
                                <p class='portal-row-tile__meta'>{$start} · {$end}</p>
                                <p class='portal-row-tile__meta'>
                                    <span class='portal-row-tile__badge portal-badge-info'>{$tipo}</span>
                                    <span class='portal-row-tile__badge portal-badge-gray'>{$department}</span>
                                </p>
                            </div>"
                        );
                    })
                    ->searchable(['title', 'description'])
                    ->sortable()
                    ->extraAttributes(['class' => 'portal-row-tile-cell']),
                TextColumn::make('scheduled_start_at')
                    ->label('Fecha y hora')
                    ->dateTime('d/m/Y H:i')
                    ->description(fn($record) => $record->scheduled_end_at?->format('H:i') ? 'Hasta ' . $record->scheduled_end_at->format('H:i') : null)
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('department.name')
                    ->label('Departamento')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tipo.nombre')
                    ->label('Tipo de cita')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('scheduled_end_at')
                    ->label('Fin')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pendiente' => 'gray',
                        'confirmado' => 'info',
                        'finalizada' => 'success',
                        'cancelada' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'confirmado' => 'Confirmada',
                        'finalizada' => 'Finalizada',
                        'cancelada' => 'Cancelada',
                    ]),
                SelectFilter::make('tipo_id')
                    ->label('Tipo')
                    ->relationship('tipo', 'nombre')
                    ->searchable()
                    ->preload(),
                Filter::make('upcoming')
                    ->label('Próximas')
                    ->query(fn(Builder $query): Builder => $query->where('scheduled_start_at', '>=', Carbon::now())),
                Filter::make('past')
                    ->label('Pasadas')
                    ->query(fn(Builder $query): Builder => $query->where('scheduled_start_at', '<', Carbon::now())),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('set_status_pendiente')
                        ->label('Marcar pendiente')
                        ->icon(Heroicon::Clock)
                        ->color('gray')
                        ->extraAttributes(['x-on:click.stop' => ''])
                        ->visible(fn(Meeting $record): bool => $record->status !== 'pendiente')
                        ->action(fn(Meeting $record) => $this->updateMeetingStatus($record, 'pendiente')),
                    Action::make('set_status_confirmado')
                        ->label('Marcar confirmado')
                        ->icon(Heroicon::CheckCircle)
                        ->color('info')
                        ->extraAttributes(['x-on:click.stop' => ''])
                        ->visible(fn(Meeting $record): bool => $record->status !== 'confirmado')
                        ->action(fn(Meeting $record) => $this->updateMeetingStatus($record, 'confirmado')),
                    Action::make('set_status_finalizada')
                        ->label('Marcar finalizada')
                        ->icon(Heroicon::CheckBadge)
                        ->color('success')
                        ->extraAttributes(['x-on:click.stop' => ''])
                        ->visible(fn(Meeting $record): bool => $record->status !== 'finalizada')
                        ->action(fn(Meeting $record) => $this->updateMeetingStatus($record, 'finalizada')),
                    Action::make('set_status_cancelada')
                        ->label('Marcar cancelada')
                        ->icon(Heroicon::XCircle)
                        ->color('danger')
                        ->extraAttributes(['x-on:click.stop' => ''])
                        ->visible(fn(Meeting $record): bool => $record->status !== 'cancelada')
                        ->action(fn(Meeting $record) => $this->updateMeetingStatus($record, 'cancelada')),
                ])
                    ->label('')
                    ->tooltip('Cambiar estado')
                    ->icon('heroicon-o-arrows-up-down')
                    ->extraAttributes(['x-on:click.stop' => ''])
                    ->color(fn(Meeting $record): string => match ($record->status) {
                        'confirmado' => 'info',
                        'finalizada' => 'success',
                        'cancelada' => 'danger',
                        default => 'gray',
                    }),
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Ver')
                        ->slideOver()
                        ->modalWidth(Width::Large)
                        ->extraModalWindowAttributes(['class' => 'portal-detail-modal'])
                        ->modalIcon(Heroicon::CalendarDays)
                        ->modalHeading(fn($record): string => (string)($record->title ?: 'Detalle de cita'))
                        ->modalDescription(fn($record): string => (string)($record->scheduled_start_at?->format('d/m/Y H:i') ?: 'Sin fecha'))
                        ->stickyModalHeader()
                        ->stickyModalFooter()
                        ->schema(fn(Schema $schema) => AppointmentInfolist::configure($schema))
                        ->extraModalFooterActions(function ($record, $livewire): array {
                            return [
                                Action::make('edit_from_view')
                                    ->label('Editar')
                                    ->icon(Heroicon::PencilSquare)
                                    ->color('primary')
                                    ->action(fn() => $livewire->replaceMountedTableAction('edit', (string)$record->getKey())),
                            ];
                        }),
                    EditAction::make()
                        ->slideOver()
                        ->successNotificationTitle('Cita actualizada')
                        ->schema(fn(Schema $schema) => AppointmentForm::configure($schema))
                        ->mutateDataUsing(function (array $data): array {
                            $data['created_by_user_id'] = PortalTaxistaContext::meetingCreatorUserId();

                            return $data;
                        }),
                    DeleteAction::make()
                        ->successNotificationTitle('Cita eliminada'),
                ])
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->extraAttributes(['x-on:click.stop' => '']),
            ])
            ->recordAction('view')
            ->toolbarActions([
                Action::make('go_dashboard')
                    ->label('Citas')
                    ->icon('heroicon-o-home')
                    ->color('gray')
                    ->outlined()
                    ->extraAttributes(['class' => 'portal-home-action toolbar-segment-pre'])
                    ->url(Dashboard::getUrl()),
                ActionGroup::make([
                    Action::make('view_table')
                        ->label('Tabla')
                        ->icon('heroicon-o-table-cells')
                        ->url(Appointments::getUrl()),
                    Action::make('view_calendar')
                        ->label('Calendario')
                        ->icon('heroicon-o-calendar-days')
                        ->url(AppointmentsCalendar::getUrl()),
                    Action::make('view_kanban')
                        ->label('Kanban')
                        ->icon('heroicon-o-view-columns')
                        ->url(AppointmentsKanban::getUrl()),
                ])
                    ->icon('heroicon-o-squares-2x2')
                    ->extraAttributes(['class' => 'toolbar-segment-pre']),
                ActionGroup::make([
                    Action::make('sort_recent')
                        ->label('Más recientes')
                        ->icon('heroicon-o-arrow-trending-down')
                        ->action(fn() => $this->sortTable('scheduled_start_at', 'desc')),
                    Action::make('sort_oldest')
                        ->label('Más antiguas')
                        ->icon('heroicon-o-arrow-trending-up')
                        ->action(fn() => $this->sortTable('scheduled_start_at', 'asc')),
                ])
                    ->label('Ordenar')
                    ->hiddenLabel()
                    ->tooltip('Ordenar')
                    ->icon('heroicon-o-arrows-up-down')
                    ->color('gray')
                    ->outlined()
                    ->extraAttributes(['class' => 'toolbar-segment-post']),
                CreateAction::make('add_appointment')
                    ->label('Añadir')
                    ->hiddenLabel()
                    ->icon('heroicon-o-plus')
                    ->extraAttributes(['class' => 'toolbar-segment-final'])
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
            ])
            ->defaultSort('scheduled_start_at', 'desc')
            ->emptyStateActions([]);
    }

    private function updateMeetingStatus(Meeting $meeting, string $status): void
    {
        $meeting->update(['status' => $status]);

        Notification::make()
            ->title('Estado de la cita actualizado')
            ->body('La cita ahora está ' . $this->resolveMeetingStatusLabel($status) . '.')
            ->success()
            ->send();
    }

    private function resolveMeetingStatusLabel(string $status): string
    {
        return match ($status) {
            'confirmado' => 'confirmada',
            'finalizada' => 'finalizada',
            'cancelada' => 'cancelada',
            default => 'pendiente',
        };
    }
}
