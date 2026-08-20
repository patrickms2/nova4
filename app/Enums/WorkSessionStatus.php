<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum WorkSessionStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Finishing = 'finishing';
    case ReportPending = 'report_pending';
    case ExitAuthorized = 'exit_authorized';
    case Finished = 'finished';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'En curso',
            self::Finishing => 'Finalizando',
            self::ReportPending => 'Parte pendiente',
            self::ExitAuthorized => 'Salida autorizada',
            self::Finished => 'Finalizada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Finishing => 'warning',
            self::ReportPending => 'warning',
            self::ExitAuthorized => 'info',
            self::Finished => 'gray',
        };
    }

    public function isOpen(): bool
    {
        return $this !== self::Finished;
    }
}
