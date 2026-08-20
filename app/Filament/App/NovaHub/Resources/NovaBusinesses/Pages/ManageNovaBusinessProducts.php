<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages;

use App\Filament\App\NovaHub\Resources\NovaBusinesses\NovaBusinessResource;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\Concerns\CanSyncNovaBusinessIntegrations;
use App\Models\NovaExternalCatalogItem;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Livewire;

final class ManageNovaBusinessProducts extends Page implements HasTable
{
    use CanSyncNovaBusinessIntegrations;
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = NovaBusinessResource::class;

    protected string $view = 'filament.app.resources.nova-businesses.pages.manage-nova-business-table';

    protected static ?string $navigationLabel = 'Productos';
    protected static ?string $navigationParentItem = 'Catálogo';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?int $navigationSort = 15;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        self::authorizeResourceAccess();
    }

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();
        $count = $record->externalCatalogItems()->where('type', 'product')->count();

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
        return 'Productos externos sincronizados para este cliente Nova.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => NovaExternalCatalogItem::query()
                ->where('nova_business_id', $this->getRecord()->id)
                ->where('type', 'product'))
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('name')->label('Nombre')->searchable()->sortable()->weight('bold')->limit(45),
                TextColumn::make('source')->label('Origen')->badge()->sortable(),
                TextColumn::make('type')->label('Tipo')->badge()->sortable(),
                TextColumn::make('sku')->label('SKU')->searchable()->toggleable(),
                TextColumn::make('price')->label('Precio')->money('EUR')->sortable(),
                TextColumn::make('regular_price')->label('Precio regular')->money('EUR')->sortable()->toggleable(),
                TextColumn::make('stock_status')->label('Stock')->badge()->toggleable(),
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
                TextColumn::make('last_synced_at')->label('Sync')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('source')->label('Origen')->options(fn (): array => NovaExternalCatalogItem::query()
                    ->where('nova_business_id', $this->getRecord()->id)
                    ->where('type', 'product')
                    ->distinct()
                    ->pluck('source', 'source')
                    ->filter()
                    ->all()),
                SelectFilter::make('status')->label('Estado')->options(fn (): array => NovaExternalCatalogItem::query()
                    ->where('nova_business_id', $this->getRecord()->id)
                    ->where('type', 'product')
                    ->distinct()
                    ->pluck('status', 'status')
                    ->filter()
                    ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('last_synced_at', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->syncIntegrationsAction(),
        ];
    }
}
