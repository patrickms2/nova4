<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaListingCategories;

use App\Filament\App\NovaHub\Resources\NovaListingCategories\Pages\CreateNovaListingCategory;
use App\Filament\App\NovaHub\Resources\NovaListingCategories\Pages\EditNovaListingCategory;
use App\Filament\App\NovaHub\Resources\NovaListingCategories\Pages\ListNovaListingCategories;
use App\Filament\App\NovaHub\Resources\NovaListingCategories\Schemas\NovaListingCategoryForm;
use App\Filament\App\NovaHub\Resources\NovaListingCategories\Tables\NovaListingCategoriesTable;
use App\Models\NovaListingCategory;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class NovaListingCategoryResource extends Resource
{
    protected static ?string $model = NovaListingCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static \UnitEnum|string|null $navigationGroup = 'Nova Hub';
    protected static \UnitEnum|string|null $navigationParentGroup = 'Catálogo';

    protected static ?string $navigationLabel = 'Listing Config';

    protected static ?string $modelLabel = 'Categoría de listado';

    protected static ?string $pluralModelLabel = 'Listing Config';

    protected static ?int $navigationSort = 4;

    protected static bool $isScopedToTenant = false;

    public static function form(Schema $schema): Schema
    {
        return NovaListingCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NovaListingCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNovaListingCategories::route('/'),
            'create' => CreateNovaListingCategory::route('/create'),
            'edit' => EditNovaListingCategory::route('/{record}/edit'),
        ];
    }
}
