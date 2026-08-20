<?php

namespace App\Enums;

enum AnnouncementType: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Danger = 'danger';
    case Success = 'success';

    public function label(): string
    {
        return match ($this) {
            self::Info => 'Info',
            self::Warning => 'Warning',
            self::Danger => 'Danger',
            self::Success => 'Success',
        };
    }
}
