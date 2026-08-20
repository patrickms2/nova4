<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaCrossSellingRules\Pages;

use App\Filament\App\NovaHub\Resources\NovaCrossSellingRules\NovaCrossSellingRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListNovaCrossSellingRules extends ListRecords
{
    protected static string $resource = NovaCrossSellingRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nueva regla'),
        ];
    }
}
