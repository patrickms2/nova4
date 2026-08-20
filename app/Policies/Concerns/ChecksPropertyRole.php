<?php

namespace App\Policies\Concerns;

use App\Models\Property;
use App\Models\User;

trait ChecksPropertyRole
{
    private function roleOnProperty(User $user, Property $property): ?string
    {
        return $user->properties()->where('property_id', $property->id)->first()?->pivot?->role;
    }

    private function isOwner(User $user, Property $property): bool
    {
        return $property->owner_id === $user->id;
    }

    private function isOwnerOrAdmin(User $user, Property $property): bool
    {
        return $this->isOwner($user, $property)
            || in_array($this->roleOnProperty($user, $property), ['owner', 'admin'], true);
    }
}
