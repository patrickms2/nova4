<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages;

use App\Filament\App\NovaHub\Resources\NovaBusinesses\NovaBusinessResource;
use App\Models\ExternalSyncMapping;
use App\Models\NovaBusiness;
use App\Models\Restaurant;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Livewire;

final class ManageNovaBusinessRestaurants extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = NovaBusinessResource::class;

    protected string $view = 'filament.app.resources.nova-businesses.pages.manage-nova-business-table';

    protected static ?string $navigationLabel = 'Restaurantes';
    protected static ?string $navigationParentItem = 'Catálogo';
    protected static \UnitEnum|string|null $navigationGroup = 'Catálogo';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?int $navigationSort = 13;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        self::authorizeResourceAccess();
    }

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();
        $count = self::restaurantQueryForBusiness((int) $record->id)->count();

        return (string) cache()->remember(
                    static::class . '.' . $record->id . '.navigation-badge',
                    now()->addMinute(),
                    fn () => $count > 0 ? (string) $count : null
                );
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Restaurantes vinculados a este cliente por mappings de integración.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => self::restaurantQueryForBusiness((int) $this->getRecord()->id))
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('name')->label('Restaurante')->searchable()->sortable()->weight('bold'),
                TextColumn::make('location.name')->label('Ubicación')->toggleable(),
                TextColumn::make('cuisine')->label('Cocina')->searchable()->sortable()->toggleable(),
                TextColumn::make('price_range')->label('Precio')->badge()->toggleable(),
                TextColumn::make('phone')->label('Teléfono')->searchable()->toggleable(),
                TextColumn::make('email')->label('Email')->searchable()->toggleable(),
                IconColumn::make('has_reservation')->label('Reservas')->boolean(),
                IconColumn::make('is_active')->label('Activo')->boolean(),
                IconColumn::make('is_featured')->label('Destacado')->boolean(),
            ])
            ->defaultSort('id', 'desc');
    }

    private static function restaurantQueryForBusiness(int $businessId): Builder
    {
        $business = NovaBusiness::query()->whereKey($businessId)->first();
        $recognitionTerms = collect($business?->settings['recognition_terms'] ?? [])
            ->push($business?->name)
            ->filter()
            ->values();

        $restaurantIds = ExternalSyncMapping::query()
            ->where(function (Builder $query) use ($recognitionTerms): void {
                foreach ($recognitionTerms as $term) {
                    $query->orWhere('business_name', 'like', '%'.(string) $term.'%');
                }
            })
            ->where('target_model', 'restaurant')
            ->whereNotNull('target_id')
            ->select('target_id');

        return Restaurant::query()->whereIn('id', $restaurantIds);
    }
}
