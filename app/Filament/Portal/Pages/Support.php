<?php

namespace App\Filament\Portal\Pages;

use App\Models\BookingDepartment;
use App\Models\TaxistaTicket;
use App\Support\Portal\PortalTaxistaContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use UnitEnum;

class Support extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.portal.pages.support';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static ?string $navigationLabel = 'Soporte';

    protected static string|UnitEnum|null $navigationGroup = 'Soporte';

    protected ?string $heading = 'Soporte';

    protected static ?int $navigationSort = 10;

    protected ?string $subheading = 'Envía un ticket de soporte con capturas de pantalla o documentos adjuntos.';

    public function table(Table $table): Table
    {
        $taxistaUserId = PortalTaxistaContext::taxistaId();

        return $table
            ->selectable(false)
            ->query(
                TaxistaTicket::query()
                    ->when(
                        $taxistaUserId,
                        fn(Builder $query): Builder => $query->where('user_id', $taxistaUserId),
                        fn(Builder $query): Builder => $query->whereRaw('1 = 0'),
                    )
            )
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('title')
                    ->label('Ticket')
                    ->html()
                    ->state(function ($record): HtmlString {
                        $title = e((string)($record->title ?: 'Sin asunto'));
                        $desc = e((string)str($record->description ?? '')->stripTags()->limit(80));
                        $date = e((string)($record->opened_at?->format('d/m/Y H:i') ?? $record->created_at?->format('d/m/Y H:i') ?? '-'));
                        $dept = e((string)($record->department?->name ?? ''));
                        $priority = e(ucfirst((string)($record->priority ?? 'media')));
                        $priorityClass = match (strtolower((string)$record->priority)) {
                            'urgente' => 'portal-badge-danger',
                            'alta' => 'portal-badge-warning',
                            'media' => 'portal-badge-info',
                            default => 'portal-badge-gray',
                        };
                        $status = e(ucfirst(str_replace('_', ' ', (string)($record->status ?? 'abierto'))));
                        $statusClass = match (strtolower((string)$record->status)) {
                            'cerrado', 'resuelto' => 'portal-badge-success',
                            'en_proceso' => 'portal-badge-info',
                            'abierto' => 'portal-badge-warning',
                            default => 'portal-badge-gray',
                        };
                        $attachCount = is_array($record->attachments) ? count($record->attachments) : 0;
                        $attachBadge = $attachCount > 0 ? "<span class='portal-row-tile__badge portal-badge-gray'>📎 {$attachCount}</span>" : '';

                        return new HtmlString(
                            "<div class='portal-row-tile'>
                                <div class='portal-row-tile__head'>
                                    <p class='portal-row-tile__title'>{$title}</p>
                                    <span class='portal-row-tile__badge {$statusClass}'>{$status}</span>
                                    {$attachBadge}
                                </div>
                                <p class='portal-row-tile__meta'>{$desc}</p>
                                <p class='portal-row-tile__meta'>{$date}" . ($dept ? " · {$dept}" : '') . " <span class='portal-row-tile__badge {$priorityClass}'>{$priority}</span></p>
                            </div>"
                        );
                    })
                    ->searchable(['title', 'description'])
                    ->extraAttributes(['class' => 'portal-row-tile-cell']),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'abierto' => 'Abierto',
                        'en_proceso' => 'En proceso',
                        'resuelto' => 'Resuelto',
                        'cerrado' => 'Cerrado',
                    ]),
                SelectFilter::make('priority')
                    ->label('Prioridad')
                    ->options([
                        'baja' => 'Baja',
                        'media' => 'Media',
                        'alta' => 'Alta',
                        'urgente' => 'Urgente',
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Ver')
                        ->slideOver()
                        ->modalWidth(Width::Large)
                        ->modalIcon(Heroicon::OutlinedLifebuoy)
                        ->modalHeading(fn($record): string => (string)($record->title ?: 'Detalle'))
                        ->stickyModalHeader()
                        ->stickyModalFooter()
                        ->schema(fn(Schema $schema): Schema => self::infolistSchema($schema)),
                    EditAction::make()
                        ->slideOver()
                        ->successNotificationTitle('Ticket actualizado')
                        ->schema(fn(Schema $schema): Schema => self::formSchema($schema))
                        ->mutateDataUsing(function (array $data): array {
                            $data['user_id'] = PortalTaxistaContext::taxistaId();

                            return $data;
                        }),
                    DeleteAction::make()
                        ->successNotificationTitle('Ticket eliminado'),
                ])->icon('heroicon-o-ellipsis-horizontal'),
            ])
            ->recordAction('view')
            ->toolbarActions([
                Action::make('go_dashboard')
                    ->label('Soporte')
                    ->icon(Heroicon::OutlinedLifebuoy)
                    ->color('gray')
                    ->outlined()
                    ->extraAttributes(['class' => 'portal-home-action toolbar-segment-pre'])
                    ->url(Dashboard::getUrl()),
                ActionGroup::make([
                    Action::make('sort_recent')
                        ->label('Más recientes')
                        ->icon('heroicon-o-arrow-trending-down')
                        ->action(fn() => $this->sortTable('created_at', 'desc')),
                    Action::make('sort_oldest')
                        ->label('Más antiguas')
                        ->icon('heroicon-o-arrow-trending-up')
                        ->action(fn() => $this->sortTable('created_at', 'asc')),
                ])
                    ->label('Ordenar')
                    ->hiddenLabel()
                    ->tooltip('Ordenar')
                    ->icon('heroicon-o-arrows-up-down')
                    ->color('gray')
                    ->outlined()
                    ->extraAttributes(['class' => 'toolbar-segment-post']),
                CreateAction::make('add_ticket')
                    ->label('Nuevo ticket')
                    ->hiddenLabel()
                    ->tooltip('Nuevo ticket de soporte')
                    ->icon('heroicon-o-plus')
                    ->extraAttributes(['class' => 'toolbar-segment-final'])
                    ->successNotificationTitle('Ticket de soporte creado')
                    ->model(TaxistaTicket::class)
                    ->schema(fn(Schema $schema): Schema => self::formSchema($schema))
                    ->fillForm(function (): array {
                        return [
                            'user_id' => PortalTaxistaContext::taxistaId(),
                            'created_by_user_id' => auth()->id(),
                            'status' => 'abierto',
                            'priority' => 'media',
                        ];
                    })
                    ->mutateDataUsing(function (array $data): array {
                        $data['user_id'] = PortalTaxistaContext::taxistaId();
                        $data['created_by_user_id'] = auth()->id();

                        return $data;
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No tienes tickets de soporte')
            ->emptyStateDescription('Puedes crear uno pulsando el botón + de arriba.')
            ->emptyStateActions([]);
    }

    public static function formSchema(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('booking_department_id')
                ->label('Departamento')
                ->options(
                    BookingDepartment::query()
                        ->where('is_active', true)
                        ->where('has_tickets_service', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                )
                ->searchable()
                ->preload()
                ->required()
                ->columnSpanFull(),
            TextInput::make('title')
                ->label('Asunto')
                ->required()
                ->maxLength(255)
                ->placeholder('Describe brevemente el problema')
                ->columnSpanFull(),
            ToggleButtons::make('priority')
                ->label('Prioridad')
                ->inline()
                ->default('media')
                ->options([
                    'baja' => 'Baja',
                    'media' => 'Media',
                    'alta' => 'Alta',
                    'urgente' => 'Urgente',
                ]),
            ToggleButtons::make('status')
                ->label('Estado')
                ->inline()
                ->default('abierto')
                ->options([
                    'abierto' => 'Abierto',
                    'en_proceso' => 'En proceso',
                    'resuelto' => 'Resuelto',
                    'cerrado' => 'Cerrado',
                ]),
            Textarea::make('description')
                ->label('Descripción')
                ->rows(4)
                ->placeholder('Explica el problema con detalle. Si es un error, indica qué estabas haciendo.')
                ->columnSpanFull(),
            FileUpload::make('attachments')
                ->label('Capturas de pantalla o documentos')
                ->multiple()
                ->maxFiles(5)
                ->maxSize(10240)
                ->acceptedFileTypes([
                    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ])
                ->directory('tickets-attachments')
                ->storeFileNamesIn('attachment_file_names')
                ->previewable()
                ->openable()
                ->downloadable()
                ->reorderable()
                ->columnSpanFull()
                ->helperText('Máx. 5 archivos · 10 MB cada uno · Imágenes (JPG, PNG, GIF, WebP) o PDF/Word'),
        ]);
    }

    public static function infolistSchema(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Infolists\Components\TextEntry::make('title')->label('Asunto'),
            \Filament\Infolists\Components\TextEntry::make('department.name')->label('Departamento')->badge()->color('gray'),
            \Filament\Infolists\Components\TextEntry::make('priority')
                ->label('Prioridad')
                ->badge()
                ->color(fn(string $state): string => match ($state) {
                    'urgente' => 'danger',
                    'alta' => 'warning',
                    'media' => 'info',
                    default => 'gray',
                }),
            \Filament\Infolists\Components\TextEntry::make('status')
                ->label('Estado')
                ->badge()
                ->color(fn(string $state): string => match ($state) {
                    'cerrado', 'resuelto' => 'success',
                    'en_proceso' => 'info',
                    'abierto' => 'warning',
                    default => 'gray',
                }),
            \Filament\Infolists\Components\TextEntry::make('description')
                ->label('Descripción')
                ->columnSpanFull()
                ->html(),
            \Filament\Infolists\Components\TextEntry::make('opened_at')
                ->label('Abierto')
                ->dateTime('d/m/Y H:i'),
            \Filament\Infolists\Components\TextEntry::make('closed_at')
                ->label('Cerrado')
                ->dateTime('d/m/Y H:i')
                ->placeholder('—'),
            \Filament\Infolists\Components\TextEntry::make('attachments')
                ->label('Adjuntos')
                ->columnSpanFull()
                ->html()
                ->state(function ($record): HtmlString {
                    $files = $record->attachments ?? [];
                    $names = $record->attachment_file_names ?? [];
                    if (empty($files)) {
                        return new HtmlString('<span class="text-gray-400">Sin adjuntos</span>');
                    }
                    $links = [];
                    foreach ($files as $path) {
                        $name = $names[$path] ?? basename((string)$path);
                        $url = asset('storage/' . $path);
                        $links[] = "<a href=\"{$url}\" target=\"_blank\" rel=\"noopener\" class=\"underline text-primary-600\">{$name}</a>";
                    }

                    return new HtmlString(implode('<br>', $links));
                }),
        ]);
    }
}
