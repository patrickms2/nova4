<?php

namespace App\Enums;

enum SemanaDia: int
{
    case domingo = 0;
    case lunes = 1;
    case martes = 2;
    case miercoles = 3;
    case jueves = 4;
    case viernes = 5;
    case sabado = 6;

    public function label(): string
    {
        return match($this) {
            self::domingo => 'Domingo',
            self::lunes => 'Lunes',
            self::martes => 'Martes',
            self::miercoles => 'Miércoles',
            self::jueves => 'Jueves',
            self::viernes => 'Viernes',
            self::sabado => 'Sábado',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
