<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaIntentRules;

use App\Filament\App\NovaHub\Resources\NovaIntentRules\Pages\CreateNovaIntentRule;
use App\Filament\App\NovaHub\Resources\NovaIntentRules\Pages\EditNovaIntentRule;
use App\Filament\App\NovaHub\Resources\NovaIntentRules\Pages\ListNovaIntentRules;
use App\Filament\App\NovaHub\Resources\NovaIntentRules\Schemas\NovaIntentRuleForm;
use App\Filament\App\NovaHub\Resources\NovaIntentRules\Tables\NovaIntentRulesTable;
use App\Models\NovaIntentRule;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class NovaIntentRuleResource extends Resource
{
    protected static ?string $model = NovaIntentRule::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static \UnitEnum|string|null $navigationGroup = 'Nova Hub';
    protected static \UnitEnum|string|null $navigationParentGroup = 'Reglas de Intent';

    protected static ?string $navigationLabel = 'Reglas de Intent';

    protected static ?string $modelLabel = 'Regla de intent';

    protected static ?string $pluralModelLabel = 'Reglas de intent';

    protected static ?int $navigationSort = 7;

    protected static bool $isScopedToTenant = false;

    public static function form(Schema $schema): Schema
    {
        return NovaIntentRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NovaIntentRulesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNovaIntentRules::route('/'),
            'create' => CreateNovaIntentRule::route('/create'),
            'edit' => EditNovaIntentRule::route('/{record}/edit'),
        ];
    }
}
