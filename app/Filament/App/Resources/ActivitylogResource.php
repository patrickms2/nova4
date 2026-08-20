<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Support\ActivityAccess;
use Illuminate\Database\Eloquent\Builder;
use Rmsramos\Activitylog\Resources\ActivitylogResource as BaseActivitylogResource;

class ActivitylogResource extends BaseActivitylogResource
{
    protected static bool $isScopedToTenant = false;

    public static function isScopedToTenant(): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false; //ActivityAccess::canViewAny(auth()->user());
    }

    public static function getEloquentQuery(): Builder
    {
        return static::baseActivityQuery();
    }

    public static function getEventFilterComponent(): \Filament\Tables\Filters\SelectFilter
    {
        return \Filament\Tables\Filters\SelectFilter::make('event')
            ->label(__('activitylog::tables.filters.event.label'))
            ->options(static::baseActivityQuery()
                ->distinct()
                ->pluck('event', 'event')
                ->mapWithKeys(fn ($value, $key) => [$key => __('activitylog::action.event.' . $value)])
            );
    }

    public static function getLogNameFilterComponent(): \Filament\Tables\Filters\SelectFilter
    {
        return \Filament\Tables\Filters\SelectFilter::make('log_name')
            ->label(__('activitylog::tables.filters.log_name.label'))
            ->options(static::baseActivityQuery()->distinct()->pluck('log_name', 'log_name')->filter());
    }

    private static function baseActivityQuery(): Builder
    {
        $query = static::getModel()::query()->withoutGlobalScopes();

        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        return ActivityAccess::scopeFor($query, $user);
    }
}
