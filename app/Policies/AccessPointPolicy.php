<?php

namespace App\Policies;

use App\Models\AccessPoint;
use App\Models\User;
use App\Policies\Concerns\ChecksPropertyRole;

class AccessPointPolicy
{
    use ChecksPropertyRole;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AccessPoint $accessPoint): bool
    {
        return $this->isOwnerOrAdmin($user, $accessPoint->property);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, AccessPoint $accessPoint): bool
    {
        return $this->isOwnerOrAdmin($user, $accessPoint->property);
    }

    public function delete(User $user, AccessPoint $accessPoint): bool
    {
        return $this->isOwnerOrAdmin($user, $accessPoint->property);
    }

    public function restore(User $user, AccessPoint $accessPoint): bool
    {
        return $this->isOwnerOrAdmin($user, $accessPoint->property);
    }

    public function forceDelete(User $user, AccessPoint $accessPoint): bool
    {
        return $this->isOwnerOrAdmin($user, $accessPoint->property);
    }

    public function open(User $user, AccessPoint $accessPoint): bool
    {
        if (! $accessPoint->is_active) {
            return false;
        }

        return $this->isOwnerOrAdmin($user, $accessPoint->property);
    }

    public function close(User $user, AccessPoint $accessPoint): bool
    {
        return $this->open($user, $accessPoint);
    }
}
