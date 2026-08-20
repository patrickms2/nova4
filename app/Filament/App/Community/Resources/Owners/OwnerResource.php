<?php

namespace App\Filament\App\Community\Resources\Owners;

use App\Models\Person;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;

class OwnerResource extends Resource
{
    protected static ?string $model = Person::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Propietarios';

    protected static ?string $modelLabel = 'Propietario';

    protected static string|\UnitEnum|null $navigationGroup = 'Propietarios';
    protected static ?string $navigationParentGroup = 'Personas';
    protected static ?int $navigationSort = 3;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([Pages\ViewOwner::class, Pages\ManageOwnerProperties::class, Pages\ManageOwnerAppointments::class, Pages\ManageOwnerDocuments::class, Pages\ManageOwnerTickets::class, Pages\ManageOwnerFees::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([Section::make('Identidad')->schema([TextInput::make('first_name')->label('Nombre')->required(), TextInput::make('last_name')->label('Apellidos'), TextInput::make('display_name')->label('Nombre visible')->required(), TextInput::make('email')->email(), TextInput::make('phone')->tel()->label('Teléfono'), TextInput::make('document_number')->label('Documento'), Select::make('communities')->label('Comunidades')->relationship('communities', 'name')->multiple()->searchable()->preload()])->columns(2)]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([Section::make(fn (Person $record): string => 'Navegación rápida — '.$record->display_name)->description('Propiedades, documentación, citas, tickets y cuotas del propietario.')->schema([TextEntry::make('properties_count')->label('Propiedades'), TextEntry::make('community_documents_count')->label('Documentos'), TextEntry::make('community_appointments_count')->label('Citas'), TextEntry::make('community_tickets_count')->label('Tickets'), TextEntry::make('community_fees_count')->label('Cuotas')])->columns(5), Section::make('Perfil')->schema([TextEntry::make('display_name')->label('Nombre'), TextEntry::make('document_number')->label('Documento'), TextEntry::make('email'), TextEntry::make('phone')->label('Teléfono'), TextEntry::make('communities.name')->label('Comunidades')->badge(), TextEntry::make('properties.name')->label('Propiedades')->badge()])->columns(2)]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('display_name')->label('Propietario')->searchable()->sortable(), TextColumn::make('document_number')->label('Documento')->searchable(), TextColumn::make('communities.name')->label('Comunidades')->badge(), TextColumn::make('properties_count')->label('Propiedades')->counts('properties'), TextColumn::make('community_documents_count')->label('Documentos')->counts('communityDocuments'), TextColumn::make('community_tickets_count')->label('Tickets')->counts('communityTickets')])
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where(fn (Builder $q) => $q->whereHas('roles', fn (Builder $r) => $r->where('role', 'owner'))->orWhereHas('communities', fn (Builder $c) => $c->where('community_person.role', 'owner')))->with(['communities', 'properties'])->withCount(['properties', 'communityDocuments', 'communityAppointments', 'communityTickets', 'communityFees']);
    }

    public static function getRelations(): array
    {
        return [RelationManagers\CommnunitiesRelationManager::class, RelationManagers\PropertiesRelationManager::class, RelationManagers\DocumentsRelationManager::class, RelationManagers\AppointmentsRelationManager::class, RelationManagers\TicketsRelationManager::class, RelationManagers\FeesRelationManager::class];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListOwners::route('/'), 'create' => Pages\CreateOwner::route('/create'), 'view' => Pages\ViewOwner::route('/{record}'), 'edit' => Pages\EditOwner::route('/{record}/edit'), 'properties' => Pages\ManageOwnerProperties::route('/{record}/properties'), 'appointments' => Pages\ManageOwnerAppointments::route('/{record}/appointments'), 'documents' => Pages\ManageOwnerDocuments::route('/{record}/documents'), 'tickets' => Pages\ManageOwnerTickets::route('/{record}/tickets'), 'fees' => Pages\ManageOwnerFees::route('/{record}/fees')];
    }
}
