<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages;

use App\Filament\App\NovaHub\Resources\NovaBusinesses\NovaBusinessResource;
use App\Filament\App\NovaHub\Resources\NovaListingCategories\Schemas\NovaListingCategoryForm;
use App\Filament\App\NovaHub\Resources\NovaListingCategories\Tables\NovaListingCategoriesTable;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Livewire;

final class ManageNovaBusinessListingCategories extends ManageRelatedRecords
{
    protected static string $resource = NovaBusinessResource::class;

    protected static string $relationship = 'listingCategories';

    protected static ?string $navigationLabel = 'Listing Config';
    protected static ?string $navigationParentItem = 'IA';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 7;

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();

        return (string) cache()->remember(
                    static::class . '.' . $record->id . '.navigation-badge',
                    now()->addMinute(),
                    fn () => $record->listingCategories()->count()
                );
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Categorías de listado configuradas para este negocio.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva categoría')
                ->icon(Heroicon::OutlinedPlus)
                ->mutateDataUsing(function (array $data): array {
                    $data['nova_business_id'] = $this->getRecord()->id;

                    return $data;
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return NovaListingCategoryForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        $table = NovaListingCategoriesTable::configure($table);
        $table->getColumn('business.name')?->toggleable(isToggledHiddenByDefault: true);

        return $table;
    }
}
