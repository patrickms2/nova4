<?php

namespace App\Filament\App\NovaHub\Resources\NovaIntegrationSyncLogs;

use App\Filament\App\NovaHub\Resources\NovaIntegrationSyncLogs\Pages\ListNovaIntegrationSyncLogs;
use App\Filament\App\NovaHub\Resources\NovaIntegrationSyncLogs\Pages\ViewNovaIntegrationSyncLog;
use App\Filament\App\NovaHub\Resources\NovaIntegrationSyncLogs\Tables\NovaIntegrationSyncLogsTable;
use App\Models\NovaIntegrationSyncLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NovaIntegrationSyncLogResource extends Resource
{
    protected static ?string $model = NovaIntegrationSyncLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static \UnitEnum|string|null $navigationGroup = 'Nova Hub';
    protected static \UnitEnum|string|null $navigationParentGroup = 'Servicios';

    protected static ?string $navigationLabel = 'Logs de sync';

    protected static ?string $modelLabel = 'Log de sync';

    protected static ?string $pluralModelLabel = 'Logs de sync';

    protected static bool $isScopedToTenant = false;

    protected static ?int $navigationSort = 24;

    public static function infolist(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return NovaIntegrationSyncLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNovaIntegrationSyncLogs::route('/'),
            'view' => ViewNovaIntegrationSyncLog::route('/{record}'),
        ];
    }
}
