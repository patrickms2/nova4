<?php

namespace App\Filament\App\Pages;

use App\Models\TaxistaTicket;
use App\Support\SupportAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
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

class Support extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.app.pages.support';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static ?string $navigationLabel = 'Soporte';

    protected static string|UnitEnum|null $navigationGroup = 'Soporte';

    protected ?string $heading = 'Soporte Interno';

    protected static ?int $navigationSort = 99;

    protected ?string $subheading = 'Reporta incidencias o solicita ayuda con capturas de pantalla y documentos.';

    protected static bool $isScopedToTenant = false;

    public static function shouldRegisterNavigation(): bool
    {
        return SupportAccess::canAccess(auth()->user());
    }

    public static function canAccess(): bool
    {
        return SupportAccess::canAccess(auth()->user());
    }

    public function table(Table $table): Table
    {
        $userId = auth()->id();

        return $table
            ->query(
                TaxistaTicket::query()//->where('created_by_user_id', $userId)
            ,)
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                      TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('title')
                    ->label('Asunto')
                    ->searchable()
                    ->limit(60),
                TextColumn::make('department.name')
                    ->label('Departamento')
                    ->badge()
                    ->color('gray')
                     ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'urgente' => 'danger',
                        'alta' => 'warning',
                        'media' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'cerrado', 'resuelto' => 'success',
                        'en_proceso' => 'info',
                        'abierto' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('attachments')
                    ->label('📎')
                    ->state(fn($record): string => is_array($record->attachments) && count($record->attachments) > 0
                        ? (string)count($record->attachments)
                        : '-')
                    ->alignCenter()
                                         ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                                         ->toggleable(isToggledHiddenByDefault: false),

            ])
            ->filters([
                SelectFilter::make('user_id')
                        ->label('Taxista')
                        ->options(fn(): array => Cache::remember('soporte_options', now()->addHours(2), function() {
                            return User::where('status', 'active')
                                ->orderBy('is_featured', 'desc')
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray();
                        })),
                        
                    SelectFilter::make('document_type')
                        ->label('Tipo')
                        ->options(TaxistaDocumentTypes::options()),
                        

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
                ViewAction::make()
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
                    ->schema(fn(Schema $schema): Schema => self::formSchema($schema)),
                DeleteAction::make()
                    ->successNotificationTitle('Ticket eliminado'),
            ])
            ->headerActions([
                CreateAction::make('add_ticket')
                    ->label('Nuevo ticket de soporte')
                    ->icon('heroicon-o-plus')
                    ->successNotificationTitle('Ticket de soporte creado')
                    ->model(TaxistaTicket::class)
                    ->schema(fn(Schema $schema): Schema => self::formSchema($schema))
                    ->fillForm(fn(): array => [
                        'created_by_user_id' => auth()->id(),
                        'status' => 'abierto',
                        'priority' => 'media',
                    ])
                    ->mutateDataUsing(function (array $data): array {
                        $data['created_by_user_id'] = auth()->id();

                        return $data;
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No tienes tickets de soporte')
            ->emptyStateDescription('Crea uno pulsando el botón "Nuevo ticket de soporte".');
    }

    public static function formSchema(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('created_by_user_id')
                ->default(fn(): ?int => auth()->id()),
            Select::make('booking_department_id')
                ->label('Departamento destino')
                ->relationship(
                    'department',
                    'name',
                    modifyQueryUsing: fn($query) => $query
                        ->where('is_active', true)
                        ->where('has_tickets_service', true)
                        ->orderBy('name')
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
            TextEntry::make('title')->label('Asunto'),
            TextEntry::make('department.name')->label('Departamento')->badge()->color('gray'),
            TextEntry::make('priority')
                ->label('Prioridad')
                ->badge()
                ->color(fn(string $state): string => match ($state) {
                    'urgente' => 'danger',
                    'alta' => 'warning',
                    'media' => 'info',
                    default => 'gray',
                }),
            TextEntry::make('status')
                ->label('Estado')
                ->badge()
                ->color(fn(string $state): string => match ($state) {
                    'cerrado', 'resuelto' => 'success',
                    'en_proceso' => 'info',
                    'abierto' => 'warning',
                    default => 'gray',
                }),
            TextEntry::make('description')
                ->label('Descripción')
                ->columnSpanFull()
                ->html(),
            TextEntry::make('opened_at')
                ->label('Abierto')
                ->dateTime('d/m/Y H:i'),
            TextEntry::make('closed_at')
                ->label('Cerrado')
                ->dateTime('d/m/Y H:i')
                ->placeholder('—'),
            TextEntry::make('attachments')
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
