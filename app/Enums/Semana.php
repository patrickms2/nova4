<?php

namespace App\Enums;

enum Semana: string
{
    case DOMINGO = 'domingo';
    case LUNES = 'lunes';
    case MARTES = 'martes';
    case MIERCOLES = 'miercoles';
    case JUEVES = 'jueves';
    case VIERNES = 'viernes';
    case SABADO = 'sabado';

    public function label(): string
    {
        return match($this) {
            self::DOMINGO => 'Domingo',
            self::LUNES => 'Lunes',
            self::MARTES => 'Martes',
            self::MIERCOLES => 'Miércoles',
            self::JUEVES => 'Jueves',
            self::VIERNES => 'Viernes',
            self::SABADO => 'Sábado',
        };
    }
    public function semana(int $dia): string
    {
        return match($dia) {
            0 => self::DOMINGO,
            1 => self::LUNES,
            2 => self::MARTES,
            3 => self::MIERCOLES,
            4 => self::JUEVES,
            5 => self::VIERNES,
            6 => self::SABADO,
        };
    }
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
