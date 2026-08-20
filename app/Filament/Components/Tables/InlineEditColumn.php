<?php

namespace App\Filament\Components\Tables;

use Closure;
use Illuminate\Support\Str;
use Filament\Support\RawJs;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\Concerns;
use Filament\Forms\Components\Concerns\HasStep;
use Filament\Tables\Columns\Contracts\Editable;
use Filament\Forms\Components\Concerns\HasInputMode;
use Filament\Tables\Columns\Concerns\CanFormatState;
use Filament\Forms\Components\Concerns\HasExtraInputAttributes;

class InlineEditColumn extends Column implements Editable
{
    use Concerns\CanBeValidated;
    use Concerns\CanUpdateState;
    use HasExtraInputAttributes;
    use CanFormatState;
    use HasInputMode;
    use HasStep;

    protected string $view = 'filament.components.tables.inline-edit-column';

    protected string | RawJs | Closure | null $mask = null;

    protected string | Closure | null $type = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disabledClick();
    }

    public function type(string | Closure | null $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->evaluate($this->type) ?? 'text';
    }

    public function mask(string | RawJs | Closure | null $mask): static
    {
        $this->mask = $mask;

        return $this;
    }

    public function getMask(): string | RawJs | null
    {
        return $this->evaluate($this->mask);
    }
}
