<?php

namespace App\Policies;

use App\Models\ProgramCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProgramCategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view program categories');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProgramCategory $programCategory): bool
    {
        return $user->hasPermissionTo('view program categories');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create program categories');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProgramCategory $programCategory): bool
    {
        return $user->hasPermissionTo('edit program categories');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProgramCategory $programCategory): bool
    {
        return $user->hasPermissionTo('delete program categories');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ProgramCategory $programCategory): bool
    {
        return $user->hasPermissionTo('edit program categories');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ProgramCategory $programCategory): bool
    {
        return $user->hasPermissionTo('delete program categories');
    }
} 