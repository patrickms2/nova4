<?php

namespace App\Filament\App\Community\Resources\CommunityAppointments;

use App\Filament\App\Community\Resources\CommunityAppointments\Pages\CreateCommunityAppointment;
use App\Filament\App\Community\Resources\CommunityAppointments\Pages\EditCommunityAppointment;
use App\Filament\App\Community\Resources\CommunityAppointments\Pages\ListCommunityAppointments;
use App\Filament\App\Community\Resources\CommunityAppointments\Pages\ViewCommunityAppointment;
use App\Filament\App\Community\Resources\CommunityAppointments\Schemas\CommunityAppointmentForm;
use App\Filament\App\Community\Resources\CommunityAppointments\Schemas\CommunityAppointmentInfolist;
use App\Filament\App\Community\Resources\CommunityAppointments\Tables\CommunityAppointmentsTable;
use App\Models\CommunityAppointment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CommunityAppointmentResource extends Resource
{
    protected static ?string $model = CommunityAppointment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Empresa';

    protected static ?string $navigationLabel = 'Citas';

    protected static ?string $modelLabel = 'Cita';

    protected static ?int $navigationSort = 8;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return CommunityAppointmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CommunityAppointmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommunityAppointmentsTable::configure($table);
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
            'index' => ListCommunityAppointments::route('/'),
            'calendar' => Pages\CalendarCommunityAppointments::route('/calendar'),
            'kanban' => Pages\KanbanCommunityAppointments::route('/kanban'),
            'create' => CreateCommunityAppointment::route('/create'),
            'view' => ViewCommunityAppointment::route('/{record}'),
            'edit' => EditCommunityAppointment::route('/{record}/edit'),
        ];
    }
}
