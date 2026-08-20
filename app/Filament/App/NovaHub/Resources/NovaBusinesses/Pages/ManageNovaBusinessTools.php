<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages;

use App\Filament\App\NovaHub\Resources\NovaBusinesses\NovaBusinessResource;
use App\Filament\Resources\ToolResource;
use App\Filament\Resources\ToolResource\Schemas\ToolForm;
use App\Filament\Resources\ToolResource\Tables\ToolsTable;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Livewire;

final class ManageNovaBusinessTools extends ManageRelatedRecords
{
    protected static string $resource = NovaBusinessResource::class;

    protected static string $relationship = 'tools';

    protected static ?string $navigationLabel = 'Tools';
    protected static \UnitEnum|string|null $navigationGroup = 'MCP';
    protected static ?string $navigationParentItem = 'MCP';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();

        return (string) cache()->remember(
                    static::class . '.' . $record->id . '.navigation-badge',
                    now()->addMinute(),
                    fn () => $record->tools()->count()
                );
    }
        protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ToolResource::promptlyAgentToolAction(),
        ];
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Tools MCP disponibles en los servidores de este negocio.';
    }

    public function form(Schema $schema): Schema
    {
        return ToolForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        $table = ToolsTable::configure($table);

        $table->modifyQueryUsing(fn (Builder $query) => $query->whereHas(
            'server',
            fn (Builder $q) => $q->where('nova_business_id', $this->getRecord()->id)
        ));

        $table->getColumn('server.novaBusiness.name')?->toggleable(isToggledHiddenByDefault: true);

        return $table;
    }
}
