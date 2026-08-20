<?php

namespace App\Filament\Portal\Pages;

use App\Enums\Icons\PhosphorIcons;
use App\Filament\Portal\Schemas\Notes\NoteForm;
use App\Models\Note;
use App\Models\Taxi\Taxista;
use App\Support\Portal\PortalTaxistaContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

class Notes extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.portal.pages.notes';

    protected static string|BackedEnum|null $navigationIcon = PhosphorIcons::ChatCenteredTextDuotone;

    protected static ?string $navigationLabel = 'Notas';

    protected static string|UnitEnum|null $navigationGroup = 'Mi Portal';

    protected ?string $heading = 'Mis Notas';

    protected static ?int $navigationSort = 5;

    protected ?string $subheading = 'Bitácora de notas del taxista.';

    protected static bool $shouldRegisterNavigation = false;

    public function table(Table $table): Table
    {
        $taxistaId = PortalTaxistaContext::taxistaId();

        return $table
            ->selectable(false)
            ->query(
                Note::query()
                    ->when($taxistaId, function ($query) use ($taxistaId) {
                        $query->where('related_type', Taxista::class)
                            ->where('related_id', $taxistaId);
                    }, fn($query) => $query->whereRaw('1 = 0'))
            )
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('title')
                    ->label('Nota')
                    ->html()
                    ->state(function ($record): HtmlString {
                        $title = e((string)($record->title ?: 'Sin titulo'));
                        $content = e((string)str($record->note ?? '')->stripTags()->limit(95));
                        $date = e((string)($record->created_at?->format('d/m/Y H:i') ?? '-'));

                        return new HtmlString(
                            "<div class='portal-row-tile'>
                                <div class='portal-row-tile__head'>
                                    <p class='portal-row-tile__title'>{$title}</p>
                                    <span class='portal-row-tile__badge portal-badge-gray'>Nota</span>
                                </div>
                                <p class='portal-row-tile__meta'>{$content}</p>
                                <p class='portal-row-tile__meta'>{$date}</p>
                            </div>"
                        );
                    })
                    ->searchable(['title'])
                    ->extraAttributes(['class' => 'portal-row-tile-cell']),
                TextColumn::make('note')
                    ->label('Nota')
                    ->limit(120)
                    ->html()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->successNotificationTitle('Nota actualizada')
                        ->schema(fn(Schema $schema) => NoteForm::configure($schema))
                        ->mutateDataUsing(function (array $data): array {
                            $taxistaId = PortalTaxistaContext::taxistaId();
                            $data['related_type'] = Taxista::class;
                            $data['related_id'] = $taxistaId;

                            return $data;
                        }),
                    DeleteAction::make()
                        ->successNotificationTitle('Nota eliminada'),
                ])->icon('heroicon-o-ellipsis-horizontal'),
            ])
            ->toolbarActions([
                Action::make('go_dashboard')
                    ->label('Notas')
                    ->icon('heroicon-o-home')
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
                CreateAction::make('add_note')
                    ->label('Añadir')
                    ->hiddenLabel()
                    ->tooltip('Añadir')
                    ->icon('heroicon-o-plus')
                    ->extraAttributes(['class' => 'toolbar-segment-final'])
                    ->successNotificationTitle('Nota creada')
                    ->model(Note::class)
                    ->schema(fn(Schema $schema) => NoteForm::configure($schema))
                    ->fillForm(function (): array {
                        $taxistaId = PortalTaxistaContext::taxistaId();

                        return [
                            'related_type' => Taxista::class,
                            'related_id' => $taxistaId,
                        ];
                    })
                    ->mutateDataUsing(function (array $data): array {
                        $taxistaId = PortalTaxistaContext::taxistaId();
                        $data['related_type'] = Taxista::class;
                        $data['related_id'] = $taxistaId;

                        return $data;
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateActions([]);
    }
}
