<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class DateExpression
{
    public static function year(string $column = 'date'): string
    {
        return match (DB::connection()->getDriverName()) {
            'pgsql' => "EXTRACT(YEAR FROM {$column})",
            'mysql' => "YEAR({$column})",
            default => "CAST(strftime('%Y', {$column}) AS INTEGER)",
        };
    }

    public static function month(string $column = 'date'): string
    {
        return match (DB::connection()->getDriverName()) {
            'pgsql' => "EXTRACT(MONTH FROM {$column})",
            'mysql' => "MONTH({$column})",
            default => "CAST(strftime('%m', {$column}) AS INTEGER)",
        };
    }

    public static function day(string $column = 'date'): string
    {
        return match (DB::connection()->getDriverName()) {
            'pgsql' => "EXTRACT(DAY FROM {$column})",
            'mysql' => "DAY({$column})",
            default => "CAST(strftime('%d', {$column}) AS INTEGER)",
        };
    }

    public static function dayOfWeek(string $column = 'date'): string
    {
        return match (DB::connection()->getDriverName()) {
            'pgsql' => "EXTRACT(DOW FROM {$column})",
            'mysql' => "WEEKDAY({$column})",
            default => "CAST(strftime('%w', {$column}) AS INTEGER)",
        };
    }

    public static function yearMonth(string $column = 'date'): string
    {
        return match (DB::connection()->getDriverName()) {
            'pgsql' => "TO_CHAR({$column}, 'YYYY-MM')",
            'mysql' => "DATE_FORMAT({$column}, '%Y-%m')",
            default => "strftime('%Y-%m', datetime({$column}))",
        };
    }

    public static function format(string $format, string $column = 'date'): string
    {
        $driver = DB::connection()->getDriverName();

        $pgFormats = [
            '%Y' => 'YYYY',
            '%m' => 'MM',
            '%d' => 'DD',
            '%w' => 'DOW',
        ];

        return match ($driver) {
            'pgsql' => "TO_CHAR({$column}, '" . str_replace(array_keys($pgFormats), array_values($pgFormats), $format) . "')",
            'mysql' => "DATE_FORMAT({$column}, '{$format}')",
            default => "strftime('{$format}', {$column})",
        };
    }
}
