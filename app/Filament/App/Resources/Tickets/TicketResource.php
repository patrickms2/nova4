<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Tickets;

use App\Enums\TicketStatus;
use App\Filament\Admin\Clusters\Taxistas\Tickets\TicketResource as ClusterTicketResource;
use App\Filament\App\Resources\Tickets\Pages\CreateTicket;
use App\Filament\App\Resources\Tickets\Pages\EditTicket;
use App\Filament\App\Resources\Tickets\Pages\ListTickets;
use App\Filament\App\Resources\Tickets\Pages\ViewTicket;
use App\Filament\Support\baseresource;
use App\Models\Taxi\Ticket;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TicketResource extends baseresource
{
    protected static ?string $model = Ticket::class;

    protected static bool $isScopedToTenant = false;

    protected static bool $isGloballySearchable = true;

    protected static string|\UnitEnum|null $navigationGroup = 'Departamentos';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Tickets';

    protected static ?int $navigationSort = 12;

    public static function form(Schema $form): Schema
    {
        return ClusterTicketResource::form($form);
    }

    public static function table(Table $table): Table
    {
        return ClusterTicketResource::table($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClusterTicketResource::infolist($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
            'create' => CreateTicket::route('/create'),
            'view' => ViewTicket::route('/{record}'),
            'edit' => EditTicket::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'description', 'status'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Estado' => static::resolveTicketStatusLabel($record),
            'Asunto' => (string) ($record->getRawOriginal('name') ?? $record->name ?? '-'),
        ];
    }

    protected static function resolveTicketStatusLabel(Model $record): string
    {
        $rawStatus = (string) ($record->getRawOriginal('status') ?? '');

        if ($rawStatus === '') {
            return '-';
        }

        $normalizedStatus = match ($rawStatus) {
            'open' => TicketStatus::abierto->value,
            'in_progress', 'in-progress' => TicketStatus::en_proceso->value,
            'resolved' => TicketStatus::resuelto->value,
            'closed' => TicketStatus::cerrado->value,
            default => $rawStatus,
        };

        $status = TicketStatus::tryFrom($normalizedStatus);

        if ($status instanceof TicketStatus) {
            return (string) $status->getLabel();
        }

        return str($rawStatus)
            ->replace(['-', '_'], ' ')
            ->headline()
            ->toString();
    }
}
