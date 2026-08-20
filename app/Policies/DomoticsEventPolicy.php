<?php

namespace App\Policies;

use App\Models\DomoticsEvent;
use App\Models\User;
use App\Policies\Concerns\ChecksPropertyRole;
use Illuminate\Auth\Access\Response;

class DomoticsEventPolicy
{
    use ChecksPropertyRole;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DomoticsEvent $domoticsEvent): bool
    {
        return $this->isOwnerOrAdmin($user, $domoticsEvent->property);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, DomoticsEvent $domoticsEvent): bool
    {
        return false;
    }

    public function delete(User $user, DomoticsEvent $domoticsEvent): bool
    {
        return $this->isOwnerOrAdmin($user, $domoticsEvent->property);
    }

    public function restore(User $user, DomoticsEvent $domoticsEvent): bool
    {
        return false;
    }

    public function forceDelete(User $user, DomoticsEvent $domoticsEvent): bool
    {
        return false;
    }
}
