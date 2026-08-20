<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;

abstract class CarTracking extends Page
{
    protected string $view = 'filament.pages.car-tracking';

    /** @var array<int, mixed> */
    public array $devices = [];

    /** @var array<int, mixed> */
    public array $positions = [];

    public bool $traccarAuthenticated = false;

    public int $remoteDevicesCount = 0;

    public int $remotePositionsCount = 0;

    public int $visibleDevicesCount = 0;

    public int $visiblePositionsCount = 0;

    /** @var array<int, string> */
    public array $allowedIdentifiersSample = [];

    public function mount(): void
    {
        if (method_exists($this, 'loadMapData')) {
            $this->loadMapData();
        }
    }

    /** @return array<int, Action> */
    public function getActions(): array
    {
        return [];
    }
}
