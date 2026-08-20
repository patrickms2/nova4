<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaIntentRules\Pages;

use App\Filament\App\NovaHub\Resources\NovaIntentRules\NovaIntentRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListNovaIntentRules extends ListRecords
{
    protected static string $resource = NovaIntentRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nueva regla'),
        ];
    }
}
