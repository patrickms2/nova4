<?php

namespace App\Filament\App\NovaHub\Resources\NovaAiProfiles;

use App\Filament\App\NovaHub\Resources\NovaAiProfiles\Pages\CreateNovaAiProfile;
use App\Filament\App\NovaHub\Resources\NovaAiProfiles\Pages\EditNovaAiProfile;
use App\Filament\App\NovaHub\Resources\NovaAiProfiles\Pages\ListNovaAiProfiles;
use App\Filament\App\NovaHub\Resources\NovaAiProfiles\Schemas\NovaAiProfileForm;
use App\Filament\App\NovaHub\Resources\NovaAiProfiles\Tables\NovaAiProfilesTable;
use App\Models\NovaAiProfile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NovaAiProfileResource extends Resource
{
    protected static ?string $model = NovaAiProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Nova Hub';
    protected static ?string $navigationParentGroup = 'IA';

    protected static ?string $navigationLabel = 'IA';

    protected static ?int $navigationSort = 4;

    protected static bool $isScopedToTenant = false;

    public static function form(Schema $schema): Schema
    {
        return NovaAiProfileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NovaAiProfilesTable::configure($table);
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
            'index' => ListNovaAiProfiles::route('/'),
            'create' => CreateNovaAiProfile::route('/create'),
            'edit' => EditNovaAiProfile::route('/{record}/edit'),
        ];
    }
}
