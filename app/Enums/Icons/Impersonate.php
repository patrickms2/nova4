<?php

namespace App\Enums\Icons;

use Filament\Support\Contracts\ScalableIcon;
use Filament\Support\Enums\IconSize;

enum Impersonate: string implements ScalableIcon
{
    case Icon = 'icon';

    public function getIconForSize(IconSize $size): string
    {
        return match ($size) {
            default => "impersonate-$this->value",
        };
    }
}
