<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Support\Carbon;
use App\Exceptions\InvalidDateRangeException;

/**
 * Trait dla modeli operujacych na zakresie dat (start_date / end_date).
 *
 * Wymagania modelu:
 *  - kolumny: start_date (date), end_date (date)
 *  - casts: start_date i end_date jako date
 *
 * Funkcje:
 *  - automatyczna walidacja start <= end przy zapisie (saving event)
 *  - helper daysCount()
 *  - helper overlapsWith($start, $end)
 *  - helper containsDate($date)
 *  - scope overlapping($start, $end) - jesli nie zdefiniowany lokalnie
 */
trait HasDateRange
{
    /**
     * Boot trait - rejestruje walidacje saving.
     */
    public static function bootHasDateRange(): void
    {
        static::saving(function ($model) {
            $start = $model->start_date;
            $end = $model->end_date;

            if ($start === null || $end === null) {
                return;
            }

            $startStr = $start instanceof \DateTimeInterface ? $start->format('Y-m-d') : (string) $start;
            $endStr = $end instanceof \DateTimeInterface ? $end->format('Y-m-d') : (string) $end;

            if ($startStr > $endStr) {
                throw InvalidDateRangeException::startAfterEnd($startStr, $endStr);
            }
        });
    }

    /**
     * Liczba dni w zakresie (inkluzywnie z oboma koncami).
     */
    public function daysCount(): int
    {
        if ($this->start_date === null || $this->end_date === null) {
            return 0;
        }

        return (int) Carbon::parse($this->start_date)->diffInDays(Carbon::parse($this->end_date)) + 1;
    }

    /**
     * Czy zakres tego modelu nakłada się z podanym zakresem dat (inclusive).
     */
    public function overlapsWith(string $startDate, string $endDate): bool
    {
        if ($this->start_date === null || $this->end_date === null) {
            return false;
        }

        $myStart = Carbon::parse($this->start_date)->format('Y-m-d');
        $myEnd = Carbon::parse($this->end_date)->format('Y-m-d');

        return $myStart <= $endDate && $myEnd >= $startDate;
    }

    /**
     * Czy podana data wpada w zakres (inclusive).
     */
    public function containsDate(string $date): bool
    {
        if ($this->start_date === null || $this->end_date === null) {
            return false;
        }

        $myStart = Carbon::parse($this->start_date)->format('Y-m-d');
        $myEnd = Carbon::parse($this->end_date)->format('Y-m-d');

        return $date >= $myStart && $date <= $myEnd;
    }
}
