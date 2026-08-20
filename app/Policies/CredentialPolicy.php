<?php

namespace App\Policies;

use App\Models\Credential;
use App\Models\User;

class CredentialPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Credential $credential): bool
    {
        return $credential->person?->user_id === $user->id
            || $credential->accessGrants->contains(fn ($grant): bool => $user->can('view', $grant));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Credential $credential): bool
    {
        return $credential->accessGrants->isNotEmpty()
            && $credential->accessGrants->every(fn ($grant): bool => $user->can('update', $grant));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Credential $credential): bool
    {
        return $this->update($user, $credential);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Credential $credential): bool
    {
        return $this->update($user, $credential);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Credential $credential): bool
    {
        return $this->update($user, $credential);
    }
}
