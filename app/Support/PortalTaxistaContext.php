<?php

namespace App\Support;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PortalTaxistaContext
{
    public static function isPortalPanel(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'portal';
    }

    public static function taxistaUserId(): ?int
    {
        $userId = auth('taxista')->id() ?? auth('web')->id();

        if (! $userId) {
            return null;
        }

        return (int) $userId;
    }

    public static function scopeTaxistaRecordQuery(Builder $query, string $column = 'taxista_user_id'): Builder
    {
        if (! static::isPortalPanel()) {
            return $query;
        }

        $taxistaUserId = static::taxistaUserId();

        if (! $taxistaUserId) {
            return $query->where($column, 0);
        }

        return $query->where($column, $taxistaUserId);
    }

    public static function scopeTaxistaOptions(Builder $query): Builder
    {
        $query
            ->orderByDesc('is_featured')
            ->orderBy('name');

        if (! static::isPortalPanel()) {
            return $query;
        }

        $taxistaUserId = static::taxistaUserId();

        if (! $taxistaUserId) {
            return $query->whereKey(0);
        }

        return $query->whereKey($taxistaUserId);
    }

    public static function canAccessTaxistaRecord(Model $record, string $column = 'taxista_user_id'): bool
    {
        if (! static::isPortalPanel()) {
            return true;
        }

        $taxistaUserId = static::taxistaUserId();

        if (! $taxistaUserId) {
            return false;
        }

        return (int) ($record->getAttribute($column) ?? 0) === $taxistaUserId;
    }
}
