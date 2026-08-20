<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PropertyPolicy
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

    public function viewAny(User $user): bool
    {
        return $user->properties()->exists() || Property::where('owner_id', $user->id)->exists();
    }

    public function view(User $user, Property $property): bool
    {
        return $this->isOwnerOrAdmin($user, $property);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Property $property): bool
    {
        return $this->isOwnerOrAdmin($user, $property);
    }

    public function delete(User $user, Property $property): bool
    {
        return $this->isOwner($user, $property);
    }

    public function restore(User $user, Property $property): bool
    {
        return $this->isOwner($user, $property);
    }

    public function forceDelete(User $user, Property $property): bool
    {
        return $this->isOwner($user, $property);
    }
}
