<?php

namespace App\Policies;

use App\Models\Campus;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CampusPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view campuses');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Campus $campus): bool
    {
        return $user->hasPermissionTo('view campuses');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create campuses');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Campus $campus): bool
    {
        return $user->hasPermissionTo('edit campuses');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Campus $campus): bool
    {
        return $user->hasPermissionTo('delete campuses');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Campus $campus): bool
    {
        return $user->hasPermissionTo('edit campuses');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Campus $campus): bool
    {
        return $user->hasPermissionTo('delete campuses');
    }
} 