<?php

namespace App\Filament\App\Resources\Announcements;

use App\Filament\App\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\App\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\App\Resources\Announcements\Pages\ListAnnouncements;
use App\Filament\App\Resources\Announcements\Schemas\AnnouncementForm;
use App\Filament\App\Resources\Announcements\Tables\AnnouncementsTable;
use App\Models\Announcement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class AnnouncementResource extends Resource
{
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $model = Announcement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|UnitEnum|null $navigationGroup = 'Empresa';

    protected static ?string $navigationLabel = 'Avisos';
    protected static ?int $navigationSort = 5;

    protected static ?string $label = 'aviso';

    protected static ?string $pluralLabel = 'avisos';

    protected static bool $isScopedToTenant = false;

    public static function form(Schema $schema): Schema
    {
        return AnnouncementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AnnouncementsTable::configure($table);
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
            'index' => ListAnnouncements::route('/'),
            'create' => CreateAnnouncement::route('/create'),
            'edit' => EditAnnouncement::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
