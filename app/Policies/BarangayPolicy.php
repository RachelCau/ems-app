<?php

namespace App\Policies;

use App\Models\Barangay;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BarangayPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view barangays');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Barangay $barangay): bool
    {
        return $user->hasPermissionTo('view barangays');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create barangays');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Barangay $barangay): bool
    {
        return $user->hasPermissionTo('edit barangays');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Barangay $barangay): bool
    {
        return $user->hasPermissionTo('delete barangays');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Barangay $barangay): bool
    {
        return $user->hasPermissionTo('edit barangays');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Barangay $barangay): bool
    {
        return $user->hasPermissionTo('delete barangays');
    }
} 