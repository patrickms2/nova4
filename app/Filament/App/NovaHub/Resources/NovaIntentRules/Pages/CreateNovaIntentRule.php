<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaIntentRules\Pages;

use App\Filament\App\NovaHub\Resources\NovaIntentRules\NovaIntentRuleResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateNovaIntentRule extends CreateRecord
{
    protected static string $resource = NovaIntentRuleResource::class;
}
