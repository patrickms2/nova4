<?php

declare(strict_types=1);

namespace App\Livewire;

use Filament\Livewire\DatabaseNotifications as BaseDatabaseNotifications;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Attributes\On;

class PortalDatabaseNotifications extends BaseDatabaseNotifications
{
    public static ?string $pollingInterval = null;

    public function getUser(): Model | Authenticatable | null
    {
        return auth('web')->user() ?? auth('taxista')->user();
    }

    #[On('databaseNotificationsSent')]
    public function refresh(): void
    {
        /** @var DatabaseNotification|null $latestNotification */
        $latestNotification = $this->getNotificationsQuery()
            ->latest('created_at')
            ->first();

        if (! $latestNotification instanceof DatabaseNotification) {
            return;
        }

        $payload = is_array($latestNotification->data) ? $latestNotification->data : [];
        $entity = strtolower(trim((string) ($payload['taxista_entity'] ?? '')));

        if (! in_array($entity, ['timeoff', 'shift_swap'], true)) {
            return;
        }

        $this->dispatch('portal-taxista-refresh', notification: $payload);
        $this->dispatch('notification-created');
        $this->dispatch('portal-taxista-refresh', notification: $payload)->to(MobilePortal::class);
        $this->dispatch('portal-taxista-refresh', notification: $payload)->to(EmployeeProfileTabs::class);
        $this->dispatch('notification-created')->to(MobilePortal::class);
    }
}
