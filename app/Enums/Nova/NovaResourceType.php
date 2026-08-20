<?php
declare(strict_types=1);

namespace App\Enums\Nova;

enum NovaResourceType: string
{
    case EloquentModel = 'eloquent_model';
    case FilamentResource = 'filament_resource';
    case LivewireComponent = 'livewire_component';
    case ApiResource = 'api_resource';
    case External = 'external';
    case Virtual = 'virtual';
}
