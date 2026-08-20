<?php

namespace App\Filament\App\NovaHub\Resources\NovaIntegrationSettings;

use App\Filament\App\NovaHub\Resources\NovaIntegrationSettings\Pages\CreateNovaIntegrationSetting;
use App\Filament\App\NovaHub\Resources\NovaIntegrationSettings\Pages\EditNovaIntegrationSetting;
use App\Filament\App\NovaHub\Resources\NovaIntegrationSettings\Pages\ListNovaIntegrationSettings;
use App\Filament\App\NovaHub\Resources\NovaIntegrationSettings\Schemas\NovaIntegrationSettingForm;
use App\Filament\App\NovaHub\Resources\NovaIntegrationSettings\Tables\NovaIntegrationSettingsTable;
use App\Models\NovaIntegrationSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NovaIntegrationSettingResource extends Resource
{
    protected static ?string $model = NovaIntegrationSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static \UnitEnum|string|null $navigationGroup = 'Nova Hub';

    protected static ?string $navigationLabel = 'API Exports';

    protected static ?string $modelLabel = 'API externa';

    protected static ?string $pluralModelLabel = 'API Exports';

    protected static bool $isScopedToTenant = false;

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return NovaIntegrationSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NovaIntegrationSettingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNovaIntegrationSettings::route('/'),
            'create' => CreateNovaIntegrationSetting::route('/create'),
            'edit' => EditNovaIntegrationSetting::route('/{record}/edit'),
        ];
    }
}
