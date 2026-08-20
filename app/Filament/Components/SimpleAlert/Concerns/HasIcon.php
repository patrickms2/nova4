<?php

namespace App\Filament\Components\SimpleAlert\Concerns;

use BackedEnum;
use Closure;
use App\Filament\Components\SimpleAlert\Enums\IconAnimation;
use Illuminate\Contracts\Support\Htmlable;

trait HasIcon
{
    protected string|BackedEnum|Htmlable|Closure|null $icon = null;

    protected Closure|IconAnimation|null $iconAnimation = null;

    public function icon(string|BackedEnum|Htmlable|Closure|null $icon, Closure|IconAnimation|null $animation = null): static
    {
        $this->icon = $icon;
        $this->iconAnimation = $animation;

        return $this;
    }

    public function getIconAnimation(): ?string
    {
        return $this->evaluate($this->iconAnimation)?->value ?? null;
    }

    public function getIcon(): BackedEnum|string|null
    {
        return $this->evaluate($this->icon);
    }
}
