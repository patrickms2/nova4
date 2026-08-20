<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaCrossSellingRules;

use App\Filament\App\NovaHub\Resources\NovaCrossSellingRules\Pages\CreateNovaCrossSellingRule;
use App\Filament\App\NovaHub\Resources\NovaCrossSellingRules\Pages\EditNovaCrossSellingRule;
use App\Filament\App\NovaHub\Resources\NovaCrossSellingRules\Pages\ListNovaCrossSellingRules;
use App\Filament\App\NovaHub\Resources\NovaCrossSellingRules\Schemas\NovaCrossSellingRuleForm;
use App\Filament\App\NovaHub\Resources\NovaCrossSellingRules\Tables\NovaCrossSellingRulesTable;
use App\Models\NovaCrossSellingRule;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class NovaCrossSellingRuleResource extends Resource
{
    protected static ?string $model = NovaCrossSellingRule::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static \UnitEnum|string|null $navigationGroup = 'Nova Hub';
    protected static \UnitEnum|string|null $navigationParentGroup = 'Cross-selling';

    protected static ?string $navigationLabel = 'Cross-selling';

    protected static ?string $modelLabel = 'Regla de cross-selling';

    protected static ?string $pluralModelLabel = 'Cross-selling';

    protected static ?int $navigationSort = 6;

    protected static bool $isScopedToTenant = false;

    public static function form(Schema $schema): Schema
    {
        return NovaCrossSellingRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NovaCrossSellingRulesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNovaCrossSellingRules::route('/'),
            'create' => CreateNovaCrossSellingRule::route('/create'),
            'edit' => EditNovaCrossSellingRule::route('/{record}/edit'),
        ];
    }
}
