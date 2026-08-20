<?php

namespace App\Filament\App\NovaHub\Resources\Dispositivo\Pages;

use App\Filament\App\NovaHub\Resources\Dispositivo\DispositivoResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\CreateRecord;

class CreateDevice extends CreateRecord
{
    protected static string $resource = DispositivoResource::class;
}
