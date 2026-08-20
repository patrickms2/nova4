<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaCrossSellingRules\Pages;

use App\Filament\App\NovaHub\Resources\NovaCrossSellingRules\NovaCrossSellingRuleResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateNovaCrossSellingRule extends CreateRecord
{
    protected static string $resource = NovaCrossSellingRuleResource::class;
}
