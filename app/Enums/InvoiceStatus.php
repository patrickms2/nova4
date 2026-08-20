<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum InvoiceStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';
    case Finalized = 'finalized';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Finalized => 'Finalized',
            self::Paid => 'Paid',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Finalized => 'info',
            self::Paid => 'success',
            self::Cancelled => 'danger',
        };
    }

    public function getIcon(): string|\BackedEnum
    {
        return match ($this) {
            self::Draft => Heroicon::OutlinedPencilSquare,
            self::Finalized => Heroicon::OutlinedDocumentCheck,
            self::Paid => Heroicon::OutlinedCheckCircle,
            self::Cancelled => Heroicon::OutlinedXCircle,
        };
    }
}
