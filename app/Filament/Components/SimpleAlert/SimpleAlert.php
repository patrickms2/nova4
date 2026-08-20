<?php

namespace App\Filament\Components\SimpleAlert;

use Closure;
use App\Filament\Components\SimpleAlert\Concerns\HasActionVerticalAlignment;
use App\Filament\Components\SimpleAlert\Concerns\HasBorder;
use App\Filament\Components\SimpleAlert\Concerns\HasColor;
use App\Filament\Components\SimpleAlert\Concerns\HasDescription;
use App\Filament\Components\SimpleAlert\Concerns\HasIcon;
use App\Filament\Components\SimpleAlert\Concerns\HasIconVerticalAlignment;
use App\Filament\Components\SimpleAlert\Concerns\HasSimple;
use App\Filament\Components\SimpleAlert\Concerns\HasTitle;
use Filament\Forms\Components\Field;

class SimpleAlert extends Field
{
    use HasActionVerticalAlignment;
    use HasBorder;
    use HasColor;
    use HasDescription;
    use HasIcon;
    use HasIconVerticalAlignment;
    use HasSimple;
    use HasTitle;

    protected string $view = 'components.simple-alert';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrated(false);
    }

    public function actions(array|Closure $actions): static
    {
        $this->actions = $actions;

        return $this;
    }
}
