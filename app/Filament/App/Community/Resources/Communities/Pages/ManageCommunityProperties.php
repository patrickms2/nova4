<?php

namespace App\Filament\App\Community\Resources\Communities\Pages;

use App\Actions\Community\TransitionWorkOrder;
use App\Filament\App\Community\Resources\Communities\CommunityResource;
use App\Filament\App\Community\Resources\WorkOrders\Pages;
use App\Models\WorkOrder;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;

class ManageCommunityProperties extends ManageRelatedRecords
{
    protected static string $resource = CommunityResource::class;

    protected static string $relationship = 'properties';

    protected static ?string $navigationLabel = 'Propiedades';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';
    protected static string|\UnitEnum|null $navigationGroup = 'Propietarios';
    protected static ?string $navigationParentGroup = 'Propietarios';
    protected static ?string $title = 'Propiedades';

    private static function transition2(WorkOrder $record, string $status): void
    {
        app(TransitionWorkOrder::class)->handle($record, $status, auth()->id());
        Notification::make()->title('Orden actualizada')->success()->send();
    }

    private static function transitionActions(): array
    {
        return [
            EditAction::make('Editar'),
            Action::make('start')->label('Iniciar')->icon('heroicon-o-play')->color('warning')->visible(fn (WorkOrder $record): bool => $record->status === 'pending')->requiresConfirmation()->action(fn (WorkOrder $record) => self::transition2($record, 'in_progress')),
            Action::make('finish')->label('Finalizar')->icon('heroicon-o-check-circle')->color('success')->visible(fn (WorkOrder $record): bool => $record->status === 'in_progress')->requiresConfirmation()->action(fn (WorkOrder $record) => self::transition2($record, 'finished')),
            Action::make('reopen')->label('Reabrir')->icon('heroicon-o-arrow-path')->visible(fn (WorkOrder $record): bool => in_array($record->status, ['finished', 'cancelled'], true))->requiresConfirmation()->action(fn (WorkOrder $record) => self::transition2($record, 'pending')),
        ];
    }

    public static function statuses(): array
    {
        return ['pending' => 'Pendiente', 'in_progress' => 'En curso', 'finished' => 'Finalizada', 'cancelled' => 'Cancelada'];
    }

    public function form(Schema $schema): Schema
    {
 return $schema
            ->components([Section::make('Propiedad')->columnSpanFull()->schema([TextInput::make('name')->label('Nombre')->columnSpanFull()->required(),Select::make('community_id')->label('Comunidad')->relationship('community', 'name')->columnSpanFull()->searchable()->preload()->required(),                        Select::make('owner_id')
                            ->label('Propietario')
                            ->relationship('owners', 'email')
                            ->searchable()
                            ->preload()
                            ->required(),  TextInput::make('unit_reference')->label('Referencia / unidad')->required(), TextInput::make('slug')->label('Identificador')->required()->unique(ignoreRecord: true), TextInput::make('address')->label('Dirección')->columnSpanFull(), Toggle::make('is_active')->label('Activa')->default(true)])->columns(2)]);

    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListWorkOrders::route('/'), 'create' => Pages\CreateWorkOrder::route('/create'), 'view' => Pages\ViewWorkOrder::route('/{record}'), 'edit' => Pages\EditWorkOrder::route('/{record}/edit')];
    }
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->mutateDataUsing(fn (array $data): array => [...$data, 'created_by' => auth()->id()])];
    }
    public function table(Table $table): Table
    {
                return $table
            ->columns([TextColumn::make('name')->label('Propiedad')->searchable()->sortable(), TextColumn::make('unit_reference')->label('Unidad')->searchable(), TextColumn::make('community.name')->label('Comunidad')->searchable(), 
            TextColumn::make('owner.email')->label('Propietario')->badge(), TextColumn::make('community_documents_count')->label('Documentos'), TextColumn::make('community_tickets_count')->label('Tickets'), IconColumn::make('is_active')->label('Activa')->boolean()])
            ->filters([SelectFilter::make('community')->relationship('community', 'name')->searchable()->preload()])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
