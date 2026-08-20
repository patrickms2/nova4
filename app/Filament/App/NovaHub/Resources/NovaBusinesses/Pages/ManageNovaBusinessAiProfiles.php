<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages;

use App\Filament\App\NovaHub\Resources\NovaAiProfiles\Schemas\NovaAiProfileForm;
use App\Filament\App\NovaHub\Resources\NovaAiProfiles\Tables\NovaAiProfilesTable;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\NovaBusinessResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Livewire;

final class ManageNovaBusinessAiProfiles extends ManageRelatedRecords
{
    protected static string $resource = NovaBusinessResource::class;

    protected static string $relationship = 'aiProfiles';

    protected static ?string $navigationLabel = 'IA';
    protected static ?string $navigationParentItem = 'IA';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();

        return (string) cache()->remember(
                    static::class . '.' . $record->id . '.navigation-badge',
                    now()->addMinute(),
                    fn () => $record->aiProfiles()->count()
                );
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Perfiles IA asociados a los servicios de este cliente.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo perfil IA')
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
        return NovaAiProfileForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        $table = NovaAiProfilesTable::configure($table);

        $table->getColumn('business.name')?->toggleable(isToggledHiddenByDefault: true);

        return $table;
    }
}
