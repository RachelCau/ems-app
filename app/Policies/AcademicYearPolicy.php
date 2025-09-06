<?php

namespace App\Policies;

use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AcademicYearPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view academic years');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AcademicYear $academicYear): bool
    {
        return $user->hasPermissionTo('view academic years');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create academic years');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AcademicYear $academicYear): bool
    {
        return $user->hasPermissionTo('edit academic years');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AcademicYear $academicYear): bool
    {
        return $user->hasPermissionTo('delete academic years');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AcademicYear $academicYear): bool
    {
        return $user->hasPermissionTo('edit academic years');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AcademicYear $academicYear): bool
    {
        return $user->hasPermissionTo('delete academic years');
    }
} 