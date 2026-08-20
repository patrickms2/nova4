<?php

namespace App\Policies;

use App\Models\Automation;
use App\Models\User;
use App\Policies\Concerns\ChecksPropertyRole;
use Illuminate\Auth\Access\Response;

class AutomationPolicy
{
    use ChecksPropertyRole;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Automation $automation): bool
    {
        return $this->isOwnerOrAdmin($user, $automation->property);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Automation $automation): bool
    {
        return $this->isOwnerOrAdmin($user, $automation->property);
    }

    public function delete(User $user, Automation $automation): bool
    {
        return $this->isOwnerOrAdmin($user, $automation->property);
    }

    public function restore(User $user, Automation $automation): bool
    {
        return $this->isOwnerOrAdmin($user, $automation->property);
    }

    public function forceDelete(User $user, Automation $automation): bool
    {
        return $this->isOwnerOrAdmin($user, $automation->property);
    }
}
