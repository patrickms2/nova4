<?php

declare(strict_types=1);

namespace App\Support\Nova;

trait HasNovaFilamentCapability
{
    protected static string $novaPanelKey = 'community';

    protected static string $novaCapabilityKey = '';

    protected static string $novaRole = 'manager';

    public static function shouldRegisterNavigation(): bool
    {
        if (static::$novaCapabilityKey === '') {
            return parent::shouldRegisterNavigation();
        }

        return app(NovaFilamentNavigation::class)->capabilityVisible(
            static::$novaPanelKey,
            static::$novaCapabilityKey,
            static::$novaRole,
        );
    }

    public static function canViewAny(): bool
    {
        if (static::$novaCapabilityKey === '') {
            return parent::canViewAny();
        }

        return app(NovaFilamentNavigation::class)->capabilityVisible(
            static::$novaPanelKey,
            static::$novaCapabilityKey,
            static::$novaRole,
        ) && parent::canViewAny();
    }
}
