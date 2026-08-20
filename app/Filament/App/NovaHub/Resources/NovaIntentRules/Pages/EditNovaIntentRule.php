<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaIntentRules\Pages;

use App\Filament\App\NovaHub\Resources\NovaIntentRules\NovaIntentRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditNovaIntentRule extends EditRecord
{
    protected static string $resource = NovaIntentRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
