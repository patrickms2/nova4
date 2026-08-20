<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages;

use App\Filament\App\NovaHub\Resources\NovaBusinesses\NovaBusinessResource;
use App\Models\ExternalSyncMapping;
use App\Models\NovaBusiness;
use App\Models\Tour;
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

final class ManageNovaBusinessTours extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = NovaBusinessResource::class;

    protected string $view = 'filament.app.resources.nova-businesses.pages.manage-nova-business-table';

    protected static ?string $navigationLabel = 'Tours';
    protected static ?string $navigationParentItem = 'Catálogo';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?int $navigationSort = 12;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        self::authorizeResourceAccess();
    }

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();

        return (string) cache()->remember(
                    static::class . '.' . $record->id . '.navigation-badge',
                    now()->addMinute(),
                    fn () => self::tourQueryForBusiness((int) $record->id)->count()
                );
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Tours vinculados a este cliente por mappings de integración.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => self::tourQueryForBusiness((int) $this->getRecord()->id))
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('name')->label('Tour')->searchable()->sortable()->weight('bold'),
                TextColumn::make('location.name')->label('Ubicación')->toggleable(),
                TextColumn::make('base_price')->label('Precio base')->money('EUR')->sortable(),
                TextColumn::make('max_capacity')->label('Capacidad')->sortable(),
                IconColumn::make('is_active')->label('Activo')->boolean(),
                IconColumn::make('is_featured')->label('Destacado')->boolean(),
            ])
            ->defaultSort('id', 'desc');
    }

    private static function tourQueryForBusiness(int $businessId): Builder
    {
        $business = NovaBusiness::query()->whereKey($businessId)->first();
        $recognitionTerms = collect($business?->settings['recognition_terms'] ?? [])
            ->push($business?->name)
            ->filter()
            ->values();

        $tourIds = ExternalSyncMapping::query()
            ->where(function (Builder $query) use ($recognitionTerms): void {
                foreach ($recognitionTerms as $term) {
                    $query->orWhere('business_name', 'like', '%'.(string) $term.'%');
                }
            })
            ->where('target_model', 'tour')
            ->whereNotNull('target_id')
            ->select('target_id');

        return Tour::query()->whereIn('id', $tourIds);
    }
}
