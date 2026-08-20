<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaCrossSellingRules\Pages;

use App\Filament\App\NovaHub\Resources\NovaCrossSellingRules\NovaCrossSellingRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditNovaCrossSellingRule extends EditRecord
{
    protected static string $resource = NovaCrossSellingRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
