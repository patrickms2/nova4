<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages;

use App\Filament\App\NovaHub\Resources\NovaBusinesses\NovaBusinessResource;
use App\Filament\App\NovaHub\Resources\NovaServices\Schemas\NovaServiceForm;
use App\Filament\App\NovaHub\Resources\NovaServices\Tables\NovaServicesTable;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Livewire;

final class ManageNovaBusinessServices extends ManageRelatedRecords
{
    protected static string $resource = NovaBusinessResource::class;

    protected static string $relationship = 'services';

    protected static ?string $navigationLabel = 'Servicios';
    protected static ?string $navigationParentItem = 'Ajustes';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();

        return (string) cache()->remember(
                    static::class . '.' . $record->id . '.navigation-badge',
                    now()->addMinute(),
                    fn () => $record->services()->count()
                );
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Servicios y capacidades contratadas por este cliente.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Añadir servicio')
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
        return NovaServiceForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return NovaServicesTable::configure($table);
    }
}
