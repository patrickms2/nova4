<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages;

use App\Filament\App\NovaHub\Resources\NovaBusinesses\NovaBusinessResource;
use App\Filament\App\NovaHub\Resources\NovaIntentRules\Schemas\NovaIntentRuleForm;
use App\Filament\App\NovaHub\Resources\NovaIntentRules\Tables\NovaIntentRulesTable;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Livewire;

final class ManageNovaBusinessIntentRules extends ManageRelatedRecords
{
    protected static string $resource = NovaBusinessResource::class;

    protected static string $relationship = 'intentRules';

    protected static ?string $navigationLabel = 'Intents';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;
    protected static ?string $navigationParentItem = 'IA';

    protected static ?int $navigationSort = 9;

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();

        return (string) cache()->remember(
                    static::class . '.' . $record->id . '.navigation-badge',
                    now()->addMinute(),
                    fn () => $record->intentRules()->count()
                );
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Reglas de detección de intent para este negocio.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva regla')
                ->icon(Heroicon::OutlinedPlus)
                ->mutateDataUsing(function (array $data): array {
                    $data['nova_business_id'] = $this->getRecord()->id;

                    return $data;
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return NovaIntentRuleForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        $table = NovaIntentRulesTable::configure($table);
        $table->getColumn('business.name')?->toggleable(isToggledHiddenByDefault: true);

        return $table;
    }
}
