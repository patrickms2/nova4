<?php

namespace App\Filament\App\Community\Resources\CommunityTickets;

use App\Filament\App\Community\Resources\CommunityTickets\Pages\CreateCommunityTicket;
use App\Filament\App\Community\Resources\CommunityTickets\Pages\EditCommunityTicket;
use App\Filament\App\Community\Resources\CommunityTickets\Pages\ListCommunityTickets;
use App\Filament\App\Community\Resources\CommunityTickets\Pages\ViewCommunityTicket;
use App\Filament\App\Community\Resources\CommunityTickets\Schemas\CommunityTicketForm;
use App\Filament\App\Community\Resources\CommunityTickets\Schemas\CommunityTicketInfolist;
use App\Filament\App\Community\Resources\CommunityTickets\Tables\CommunityTicketsTable;
use App\Models\CommunityTicket;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CommunityTicketResource extends Resource
{
    protected static ?string $model = CommunityTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|\UnitEnum|null $navigationGroup = 'Propietarios';
    protected static ?string $navigationParentGroup = 'Propietarios';

    protected static ?string $navigationLabel = 'Tickets';

    protected static ?string $modelLabel = 'Ticket';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return CommunityTicketForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CommunityTicketInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommunityTicketsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommunityTickets::route('/'),
            'create' => CreateCommunityTicket::route('/create'),
            'view' => ViewCommunityTicket::route('/{record}'),
            'edit' => EditCommunityTicket::route('/{record}/edit'),
        ];
    }
}
