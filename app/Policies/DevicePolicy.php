<?php

namespace App\Policies;

use App\Models\Device;
use App\Models\User;
use App\Policies\Concerns\ChecksPropertyRole;
use Illuminate\Auth\Access\Response;

class DevicePolicy
{
    use ChecksPropertyRole;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Device $device): bool
    {
        return $this->isOwnerOrAdmin($user, $device->property);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Device $device): bool
    {
        $role = $this->roleOnProperty($user, $device->property);

        return $this->isOwnerOrAdmin($user, $device->property)
            || $role === 'technician';
    }

    public function delete(User $user, Device $device): bool
    {
        return $this->isOwnerOrAdmin($user, $device->property);
    }

    public function restore(User $user, Device $device): bool
    {
        return $this->isOwnerOrAdmin($user, $device->property);
    }

    public function forceDelete(User $user, Device $device): bool
    {
        return $this->isOwnerOrAdmin($user, $device->property);
    }
}
