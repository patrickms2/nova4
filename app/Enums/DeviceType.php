<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DeviceType: string implements HasColor, HasLabel
{
    case Lock = 'lock';
    case Sensor = 'sensor';
    case Camera = 'camera';
    case Light = 'light';
    case Thermostat = 'thermostat';
    case Hub = 'hub';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Lock => 'Cerradura',
            self::Sensor => 'Sensor',
            self::Camera => 'Cámara',
            self::Light => 'Iluminación',
            self::Thermostat => 'Termostato',
            self::Hub => 'Hub',
            self::Other => 'Otro',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Lock => 'primary',
            self::Sensor => 'info',
            self::Camera => 'warning',
            self::Light => 'warning',
            self::Thermostat => 'danger',
            self::Hub => 'success',
            self::Other => 'gray',
        };
    }
}
