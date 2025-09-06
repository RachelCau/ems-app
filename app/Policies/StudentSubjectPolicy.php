<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentSubjectPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view student subjects');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, $studentSubject): bool
    {
        return $user->hasPermissionTo('view student subjects');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create student subjects');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, $studentSubject): bool
    {
        return $user->hasPermissionTo('edit student subjects');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, $studentSubject): bool
    {
        return $user->hasPermissionTo('delete student subjects');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, $studentSubject): bool
    {
        return $user->hasPermissionTo('edit student subjects');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, $studentSubject): bool
    {
        return $user->hasPermissionTo('delete student subjects');
    }
} 