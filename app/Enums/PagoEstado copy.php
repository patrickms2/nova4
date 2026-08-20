<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Collection;

enum PagoEstado: int implements HasLabel, HasColor, HasIcon
{


    case PENDIENTE = 1;
    case PENDIENTE_NOTIFICADO = 2;
    case PAGADO = 3;
    case PAGADO_ADMIN = 4;
    case PAGADO_TPV = 5;
    case ERROR = 6;
    case ERROR_TPV = 7;
    case CANCELADO = 8;
    case PAGO_PARCIAL = 9;

    public function getValue()
    {
        return match ($this) {

            self::PENDIENTE => 'PENDIENTE',
            self::PENDIENTE_NOTIFICADO => 'PENDIENTE_NOTIFICADO',
            self::PAGADO => 'PAGADO',
            self::PAGADO_ADMIN => 'PAGADO_ADMIN',
            self::PAGADO_TPV => 'PAGADO_TPV',
            self::ERROR => 'ERROR',
            self::ERROR_TPV => 'ERROR_TPV',
            self::CANCELADO => 'CANCELADO',
            self::PAGO_PARCIAL => 'PAGO_PARCIAL',
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {

            self::PENDIENTE => 'Pendiente',
            self::PENDIENTE_NOTIFICADO => 'Pendiente Pago Notificado',
            self::PAGADO => 'Pagado',
            self::PAGADO_ADMIN => 'Pagado Admin',
            self::PAGADO_TPV => 'Pagado TPV',
            self::ERROR => 'Error',
            self::ERROR_TPV => 'Error TPV',
            self::CANCELADO => 'Cancelado',
            self::PAGO_PARCIAL => 'Pago Parcial',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDIENTE => 'info',
            self::PENDIENTE_NOTIFICADO => 'info',
            self::PAGADO => 'success',
            self::PAGADO_ADMIN => 'success',
            self::PAGADO_TPV => 'success',
            self::ERROR => 'danger',
            self::ERROR_TPV => 'danger',
            self::CANCELADO => 'danger',
            self::PAGO_PARCIAL => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDIENTE => 'heroicon-m-clock',
            self::PENDIENTE_NOTIFICADO => 'heroicon-m-clock',
            self::PAGADO => 'heroicon-m-check-circle',
            self::PAGADO_ADMIN => 'heroicon-m-check-circle',
            self::PAGADO_TPV => 'heroicon-m-check-circle',
            self::ERROR => 'heroicon-m-exclamation-circle',
            self::ERROR_TPV => 'heroicon-m-exclamation-circle',
            self::CANCELADO => 'heroicon-m-exclamation-circle',
            self::PAGO_PARCIAL => 'heroicon-m-exclamation-circle',
        };
    }

    // Static methods for backward compatibility
    public static function select()
    {
        return collect(self::cases())
            ->map(fn($case) => ['<option value=\"' . $case->getValue() . '\" selected >' . $case->getLabel() . '</option>'])
            ->flatten()
            ->implode('');
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }


}
