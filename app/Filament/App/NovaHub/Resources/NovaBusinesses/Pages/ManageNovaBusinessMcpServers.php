<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages;

use App\Filament\App\NovaHub\Resources\NovaBusinesses\NovaBusinessResource;
use App\Filament\Resources\ServerResource\Schemas\ServerForm;
use App\Filament\Resources\ServerResource\Tables\ServersTable;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Livewire;

final class ManageNovaBusinessMcpServers extends ManageRelatedRecords
{
    protected static string $resource = NovaBusinessResource::class;

    protected static string $relationship = 'mcpServers';

    protected static ?string $navigationLabel = 'MCP';
    protected static ?string $navigationParentItem = 'Clientes Nova';


    protected static string|\UnitEnum|null $navigationGroup = 'MCP';
    protected static ?string $navigationParentGroup = 'Clientes';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();

        return (string) cache()->remember(
                    static::class . '.' . $record->id . '.navigation-badge',
                    now()->addMinute(),
                    fn () => $record->mcpServers()->count()
                );
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Servidores MCP asociados a los servicios de este cliente.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo MCP')
                ->icon(Heroicon::OutlinedPlus)
                ->color('danger')
                ->mutateDataUsing(function (array $data): array {
                    $data['nova_business_id'] = $this->getRecord()->id;

                    return $data;
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return ServerForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        $table = ServersTable::configure($table);

        $table->getColumn('novaBusiness.name')?->toggleable(isToggledHiddenByDefault: true);

        return $table;
    }
}
