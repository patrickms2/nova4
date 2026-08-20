<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaServices\Pages;

use App\Filament\App\NovaHub\Resources\NovaServices\NovaServiceResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateNovaService extends CreateRecord
{
    protected static string $resource = NovaServiceResource::class;
}
