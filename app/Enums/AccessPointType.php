<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AccessPointType: string implements HasLabel
{
    case Gate = 'gate';
    case Door = 'door';
    case Garage = 'garage';
    case PedestrianDoor = 'pedestrian_door';
    case Light = 'light';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Gate => 'Portón',
            self::Door => 'Puerta',
            self::Garage => 'Garaje',
            self::PedestrianDoor => 'Puerta peatonal',
            self::Light => 'Luz',
            self::Other => 'Otro',
        };
    }
}
