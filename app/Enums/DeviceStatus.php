<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DeviceStatus: string implements HasColor, HasLabel
{
    case Online = 'online';
    case Offline = 'offline';
    case Unknown = 'unknown';

    public function getLabel(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::Offline => 'Offline',
            self::Unknown => 'Desconocido',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Online => 'success',
            self::Offline => 'danger',
            self::Unknown => 'gray',
        };
    }
}
