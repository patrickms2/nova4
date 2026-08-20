<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\Concerns;

use Filament\Support\Icons\Heroicon;

use App\Models\NovaIntegrationSetting;
use App\Services\Nova\NovaLatePointApiSyncService;
use App\Services\Nova\NovaMagentoApiSyncService;
use App\Services\Nova\NovaWooCommerceApiSyncService;
use App\Services\Nova\NovaWooLatePointDatabaseSyncService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Throwable;

trait CanSyncNovaBusinessIntegrations
{
    protected function syncIntegrationsAction(): Action
    {
        return Action::make('syncIntegrations')
            ->label('Sincronizar')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Sincronizar integraciones')
            ->modalDescription('Se sincronizarán las integraciones activas de este cliente Nova.')
            ->action(function (): void {
                $settings = $this->getRecord()->integrationSettings()
                    ->active()
                    ->get();

                if ($settings->isEmpty()) {
                    Notification::make()
                        ->title('No hay integraciones activas')
                        ->warning()
                        ->send();

                    return;
                }

                $synced = 0;
                $failed = 0;

                foreach ($settings as $setting) {
                    try {
                        $this->syncIntegrationSetting($setting);
                        $synced++;
                    } catch (Throwable $exception) {
                        $failed++;
                        report($exception);
                    }
                }

                Notification::make()
                    ->title('Sincronización completada')
                    ->body("Integraciones sincronizadas: {$synced}. Fallidas: {$failed}.")
                    ->success($failed === 0)
                    ->warning($failed > 0)
                    ->send();
            });
    }

    private function syncIntegrationSetting(NovaIntegrationSetting $setting): array
    {
        return match ($setting->source_type) {
            'magento' => app(NovaMagentoApiSyncService::class)->sync($setting, true),
            'latepoint' => $setting->connection_type === 'api'
                ? app(NovaLatePointApiSyncService::class)->sync($setting, true)
                : app(NovaWooLatePointDatabaseSyncService::class)->sync($setting, true),
            'woo', 'wordpress', 'woo_latepoint' => $setting->connection_type === 'api'
                ? app(NovaWooCommerceApiSyncService::class)->sync($setting, true)
                : app(NovaWooLatePointDatabaseSyncService::class)->sync($setting, true),
            default => [],
        };
    }
}
