<?php

namespace App\Policies;

use App\Models\AccessGrant;
use App\Models\User;
use App\Policies\Concerns\ChecksPropertyRole;
use Illuminate\Auth\Access\Response;

class AccessGrantPolicy
{
    use ChecksPropertyRole;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AccessGrant $accessGrant): bool
    {
        return $accessGrant->user_id === $user->id
            || $this->isOwnerOrAdmin($user, $accessGrant->property);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, AccessGrant $accessGrant): bool
    {
        return $this->isOwnerOrAdmin($user, $accessGrant->property);
    }

    public function delete(User $user, AccessGrant $accessGrant): bool
    {
        return $this->isOwnerOrAdmin($user, $accessGrant->property);
    }

    public function restore(User $user, AccessGrant $accessGrant): bool
    {
        return $this->isOwnerOrAdmin($user, $accessGrant->property);
    }

    public function forceDelete(User $user, AccessGrant $accessGrant): bool
    {
        return $this->isOwnerOrAdmin($user, $accessGrant->property);
    }
}
