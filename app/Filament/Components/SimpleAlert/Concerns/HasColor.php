<?php

namespace App\Filament\Components\SimpleAlert\Concerns;

use Closure;

trait HasColor
{
    protected Closure|string $color = 'gray';

    public function color(Closure|string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getColor(): string
    {
        return $this->evaluate($this->color);
    }
}
