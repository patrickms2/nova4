<?php

namespace App\Filament\Portal\Pages;

use App\Enums\Icons\PhosphorIcons;
use App\Filament\Portal\Schemas\Tickets\TicketForm;
use App\Filament\Portal\Schemas\Tickets\TicketInfolist;
use App\Models\Taxi\Ticket;
use App\Support\Portal\PortalTaxistaContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

class Tickets extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.portal.pages.tickets';

    protected static string|BackedEnum|null $navigationIcon = PhosphorIcons::FilesDuotone;

    protected static ?string $navigationLabel = 'Tickets';

    protected static string|UnitEnum|null $navigationGroup = 'Mi Portal';

    protected ?string $heading = 'Mis Tickets';

    protected static ?int $navigationSort = 4;

    protected ?string $subheading = 'Incidencias y consultas del taxista.';

    public function table(Table $table): Table
    {
        $taxistaId = PortalTaxistaContext::taxistaId();

        return $table
            ->selectable(false)
            ->query(
                Ticket::query()
                    ->when($taxistaId, fn($query) => $query->where('usuario_id', $taxistaId), fn($query) => $query->whereRaw('1 = 0'))
            )
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                    ->label('Tema')
                    ->html()
                    ->state(function ($record): HtmlString {
                        $name = e((string)($record->name ?: 'Sin tema'));
                        $department = e((string)($record->departamento?->nombre ?? 'Sin departamento'));
                        $date = e((string)($record->start_date?->format('d/m/Y H:i') ?? '-'));
                        $priority = e(ucfirst((string)($record->priority ?? 'media')));
                        $priorityClass = match (strtolower((string)$record->priority)) {
                            'critica', 'critical' => 'portal-badge-danger',
                            'alta', 'high' => 'portal-badge-warning',
                            'media', 'medium' => 'portal-badge-info',
                            default => 'portal-badge-gray',
                        };
                        $status = e(ucfirst((string)($record->status ?? 'abierto')));
                        $statusClass = match (strtolower((string)$record->status)) {
                            'cerrado', 'closed', 'resuelto', 'resolved' => 'portal-badge-success',
                            'en_progreso', 'in_progress' => 'portal-badge-info',
                            'abierto', 'open', 'nuevo', 'new' => 'portal-badge-warning',
                            default => 'portal-badge-gray',
                        };

                        return new HtmlString(
                            "<div class='portal-row-tile'>
                                <div class='portal-row-tile__head'>
                                    <p class='portal-row-tile__title'>{$name}</p>
                                    <span class='portal-row-tile__badge {$statusClass}'>{$status}</span>
                                </div>
                                <p class='portal-row-tile__meta'>{$date} · {$department}</p>
                                <p class='portal-row-tile__meta'><span class='portal-row-tile__badge {$priorityClass}'>{$priority}</span></p>
                            </div>"
                        );
                    })
                    ->searchable(['name', 'description', 'lugar'])
                    ->extraAttributes(['class' => 'portal-row-tile-cell']),
                TextColumn::make('tipo.nombre')
                    ->label('Tipo')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('departamento.nombre')
                    ->label('Departamento')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->color(fn(string $state): string => match (strtolower($state)) {
                        'critica', 'critical' => 'danger',
                        'alta', 'high' => 'warning',
                        'media', 'medium' => 'info',
                        'baja', 'low' => 'gray',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match (strtolower($state)) {
                        'cerrado', 'closed', 'resuelto', 'resolved' => 'success',
                        'en_progreso', 'in_progress' => 'info',
                        'abierto', 'open', 'nuevo', 'new' => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('start_date')
                    ->label('Inicio')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'abierto' => 'Abierto',
                        'en_progreso' => 'En progreso',
                        'resuelto' => 'Resuelto',
                        'cerrado' => 'Cerrado',
                        'nuevo' => 'Nuevo',
                    ]),
                SelectFilter::make('priority')
                    ->label('Prioridad')
                    ->options([
                        'baja' => 'Baja',
                        'media' => 'Media',
                        'alta' => 'Alta',
                        'critica' => 'Crítica',
                    ]),
                SelectFilter::make('tipo_id')
                    ->label('Tipo')
                    ->relationship('tipo', 'nombre')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('departamento_id')
                    ->label('Departamento')
                    ->relationship('departamento', 'nombre')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Ver')
                        ->slideOver()
                        ->modalWidth(Width::Large)
                        ->extraModalWindowAttributes(['class' => 'portal-detail-modal'])
                        ->modalIcon(Heroicon::Ticket)
                        ->modalHeading(fn($record): string => (string)($record->name ?: 'Detalle de ticket'))
                        ->modalDescription(fn($record): string => (string)($record->start_date?->format('d/m/Y H:i') ?: 'Sin fecha'))
                        ->stickyModalHeader()
                        ->stickyModalFooter()
                        ->schema(fn(Schema $schema) => TicketInfolist::configure($schema))
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
                        ->successNotificationTitle('Ticket actualizado')
                        ->schema(fn(Schema $schema) => TicketForm::configure($schema))
                        ->mutateDataUsing(function (array $data): array {
                            $taxistaId = PortalTaxistaContext::taxistaId();
                            $data['usuario_id'] = $taxistaId;
                            $data['creado_por'] = $taxistaId;

                            return $data;
                        }),
                    DeleteAction::make()
                        ->successNotificationTitle('Ticket eliminado'),
                ])->icon('heroicon-o-ellipsis-horizontal'),
            ])
            ->recordAction('view')
            ->toolbarActions([
                Action::make('go_dashboard')
                    ->label('Tickets')
                    ->icon('heroicon-o-home')
                    ->color('gray')
                    ->outlined()
                    ->extraAttributes(['class' => 'portal-home-action toolbar-segment-pre'])
                    ->url(Dashboard::getUrl()),
                ActionGroup::make([
                    Action::make('sort_recent')
                        ->label('Más recientes')
                        ->icon('heroicon-o-arrow-trending-down')
                        ->action(fn() => $this->sortTable('start_date', 'desc')),
                    Action::make('sort_oldest')
                        ->label('Más antiguas')
                        ->icon('heroicon-o-arrow-trending-up')
                        ->action(fn() => $this->sortTable('start_date', 'asc')),
                ])
                    ->label('Ordenar')
                    ->hiddenLabel()
                    ->tooltip('Ordenar')
                    ->icon('heroicon-o-arrows-up-down')
                    ->color('gray')
                    ->outlined()
                    ->extraAttributes(['class' => 'toolbar-segment-post']),
                CreateAction::make('add_ticket')
                    ->label('Añadir')
                    ->hiddenLabel()
                    ->tooltip('Añadir')
                    ->icon('heroicon-o-plus')
                    ->extraAttributes(['class' => 'toolbar-segment-final'])
                    ->successNotificationTitle('Ticket creado')
                    ->model(Ticket::class)
                    ->schema(fn(Schema $schema) => TicketForm::configure($schema))
                    ->fillForm(function (): array {
                        $taxistaId = PortalTaxistaContext::taxistaId();

                        return [
                            'usuario_id' => $taxistaId,
                            'creado_por' => $taxistaId,
                        ];
                    })
                    ->mutateDataUsing(function (array $data): array {
                        $data['usuario_id'] = PortalTaxistaContext::taxistaId();
                        $data['creado_por'] = PortalTaxistaContext::taxistaId();

                        return $data;
                    }),
            ])
            ->defaultSort('start_date', 'desc')
            ->emptyStateActions([]);
    }
}
